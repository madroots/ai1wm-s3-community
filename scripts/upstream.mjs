#!/usr/bin/env node
// Reads the two public upstream signals and compares them with upstream/state.json.
//
//   node scripts/upstream.mjs changelog [--since <ver>] [--file <html>]
//   node scripts/upstream.mjs base
//   node scripts/upstream.mjs status          # writes GitHub step outputs if $GITHUB_OUTPUT is set
//   node scripts/upstream.mjs mark [--s3 <ver>] [--base <ver>]   # always refreshes last_ok_check
//
// Signal A: ServMask help-center changelog table for the S3 Client extension.
// Signal B: the base plugin on wordpress.org. Its extension registry declares
//           the minimum S3 extension version it requires, which moves whenever
//           ServMask ships a new S3 release.
//
// Every parsing step fails loudly. A silently empty result would look like
// "nothing new" and the fork would quietly stop following upstream.

import { appendFileSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const STATE_FILE = join(root, 'upstream', 'state.json');

const CHANGELOG_URL = 'https://help.servmask.com/knowledgebase/s3-client-extension-changelog/';
const BASE_INFO_URL = 'https://api.wordpress.org/plugins/info/1.0/all-in-one-wp-migration.json';
const BASE_EXTENSIONS_URL = (version) =>
	`https://plugins.svn.wordpress.org/all-in-one-wp-migration/tags/${version}/lib/model/class-ai1wm-extensions.php`;

const MIN_EXPECTED_ROWS = 20;

class UpstreamError extends Error {}

export function compareVersions(a, b) {
	const x = a.split('.').map(Number);
	const y = b.split('.').map(Number);
	for (let i = 0; i < Math.max(x.length, y.length); i++) {
		const d = (x[i] || 0) - (y[i] || 0);
		if (d !== 0) {
			return d < 0 ? -1 : 1;
		}
	}
	return 0;
}

async function get(url) {
	const response = await fetch(url, {
		headers: { 'User-Agent': 'ai1wm-s3-community-watcher (+https://github.com/madroots/ai1wm-s3-community)' },
		signal: AbortSignal.timeout(30000),
	});
	if (!response.ok) {
		throw new UpstreamError(`GET ${url} returned HTTP ${response.status}`);
	}
	return response.text();
}

const ENTITIES = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ' };

function decode(text) {
	return text
		.replace(/<br\s*\/?>/gi, '\n')
		.replace(/<[^>]+>/g, ' ')
		.replace(/&#x([0-9a-f]+);/gi, (_, h) => String.fromCodePoint(parseInt(h, 16)))
		.replace(/&#(\d+);/g, (_, d) => String.fromCodePoint(Number(d)))
		.replace(/&([a-z]+);/gi, (m, n) => ENTITIES[n.toLowerCase()] ?? m)
		.split('\n')
		.map((line) => line.replace(/\s+/g, ' ').trim())
		.filter(Boolean)
		.join('\n');
}

export function parseChangelog(html) {
	const table = html.match(/<table[\s\S]*?<\/table>/i);
	if (!table) {
		throw new UpstreamError('changelog page contains no <table>');
	}
	const rows = [...table[0].matchAll(/<tr[\s\S]*?<\/tr>/gi)].map((row) =>
		[...row[0].matchAll(/<t[hd][\s\S]*?<\/t[hd]>/gi)].map((cell) => decode(cell[0])),
	);

	const [header, ...body] = rows;
	const expected = ['version', 'released date', 'change log'];
	if (!header || header.length !== 3 || header.some((cell, i) => cell.toLowerCase() !== expected[i])) {
		throw new UpstreamError(`unexpected changelog table header: ${JSON.stringify(header)}`);
	}

	const entries = body.map(([version, date, text]) => {
		if (!/^\d+(\.\d+)+$/.test(version)) {
			throw new UpstreamError(`unparseable version cell: ${JSON.stringify(version)}`);
		}
		const m = /^(\d\d)\/(\d\d)\/(\d{4})$/.exec(date);
		if (!m) {
			throw new UpstreamError(`unparseable date cell for ${version}: ${JSON.stringify(date)}`);
		}
		if (!text) {
			throw new UpstreamError(`empty change log cell for ${version}`);
		}
		return { version, date: `${m[3]}-${m[1]}-${m[2]}`, text };
	});

	if (entries.length < MIN_EXPECTED_ROWS) {
		throw new UpstreamError(`only ${entries.length} changelog rows parsed, expected at least ${MIN_EXPECTED_ROWS}`);
	}
	for (let i = 1; i < entries.length; i++) {
		if (compareVersions(entries[i - 1].version, entries[i].version) <= 0) {
			throw new UpstreamError(`changelog is not strictly newest-first at ${entries[i - 1].version} / ${entries[i].version}`);
		}
	}
	return entries;
}

async function changelog({ since, file }) {
	const html = file ? readFileSync(file, 'utf8') : await get(CHANGELOG_URL);
	const entries = parseChangelog(html);
	const selected = since ? entries.filter((entry) => compareVersions(entry.version, since) > 0) : entries;
	return selected.reverse(); // oldest first, the order they should be applied
}

export function parseRequiredS3Version(php) {
	const block = php.match(/defined\(\s*'AI1WMNE_PLUGIN_NAME'\s*\)[\s\S]*?\);\s*\}/);
	const required = block && block[0].match(/'requires'\s*=>\s*'([\d.]+)'/);
	if (!required) {
		throw new UpstreamError('S3 extension block with a "requires" value not found in the base plugin registry');
	}
	return required[1];
}

async function base() {
	const info = JSON.parse(await get(BASE_INFO_URL));
	if (!/^\d+(\.\d+)+$/.test(info.version || '')) {
		throw new UpstreamError(`unexpected base plugin version: ${JSON.stringify(info.version)}`);
	}
	const registry = await get(BASE_EXTENSIONS_URL(info.version));
	return { version: info.version, requires_s3: parseRequiredS3Version(registry) };
}

function readState() {
	return JSON.parse(readFileSync(STATE_FILE, 'utf8'));
}

async function status() {
	const state = readState();
	const [entries, baseInfo] = await Promise.all([changelog({}), base()]);
	const latestS3 = entries[entries.length - 1].version;

	// Signal B can only trail signal A (the base plugin ships after the
	// extension). B ahead of A means the changelog page is stale or broken.
	if (compareVersions(baseInfo.requires_s3, latestS3) > 0) {
		throw new UpstreamError(
			`base plugin requires S3 ${baseInfo.requires_s3} but the changelog only lists ${latestS3}; signals disagree`,
		);
	}

	const pending = entries.filter((entry) => compareVersions(entry.version, state.last_processed_s3) > 0);
	const result = {
		s3_latest: latestS3,
		s3_last_processed: state.last_processed_s3,
		s3_pending: pending.length,
		base_latest: baseInfo.version,
		base_last_reviewed: state.last_reviewed_base,
		base_new: compareVersions(baseInfo.version, state.last_reviewed_base) > 0,
		s3_new: pending.length > 0,
	};

	if (process.env.GITHUB_OUTPUT) {
		appendFileSync(process.env.GITHUB_OUTPUT, Object.entries(result).map(([k, v]) => `${k}=${v}\n`).join(''));
	}
	return result;
}

function mark({ s3, base: baseVersion }) {
	const state = readState();
	if (s3) {
		state.last_processed_s3 = s3;
	}
	if (baseVersion) {
		state.last_reviewed_base = baseVersion;
	}
	state.last_ok_check = new Date().toISOString().slice(0, 10);
	writeFileSync(STATE_FILE, `${JSON.stringify(state, null, 2)}\n`);
	return state;
}

function options(args) {
	const out = {};
	for (let i = 0; i < args.length; i += 2) {
		if (!args[i].startsWith('--') || args[i + 1] === undefined) {
			throw new UpstreamError(`bad option ${args[i]}`);
		}
		out[args[i].slice(2)] = args[i + 1];
	}
	return out;
}

async function main([command, ...args]) {
	const opts = options(args);
	switch (command) {
		case 'changelog':
			return changelog(opts);
		case 'base':
			return base();
		case 'status':
			return status();
		case 'mark':
			return mark(opts);
		default:
			throw new UpstreamError('usage: upstream.mjs changelog|base|status|mark');
	}
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
	main(process.argv.slice(2)).then(
		(result) => console.log(JSON.stringify(result, null, 2)),
		(error) => {
			console.error(`error: ${error.message}`);
			process.exit(error instanceof UpstreamError ? 2 : 1);
		},
	);
}
