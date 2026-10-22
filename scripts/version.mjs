#!/usr/bin/env node
// Single owner of every place the plugin version is declared.
//
//   node scripts/version.mjs show
//   node scripts/version.mjs check [expected-version]
//   node scripts/version.mjs bump <X.Y.Z> --note "text" [--note "text" ...]
//                            [--upstream <ver>] [--base-reviewed <ver>]
//   node scripts/version.mjs notes <X.Y.Z>      # CHANGELOG.md section body
//
// Version scheme: <upstream S3 version>.<fork patch>, e.g. 1.59.0 means
// "reviewed through ServMask S3 Client 1.59".

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const file = (name) => join(root, name);
const read = (name) => readFileSync(file(name), 'utf8');

const PLUGIN = 'all-in-one-wp-migration-s3-client-extension.php';
const SEMVER = /^(\d+)\.(\d+)\.(\d+)$/;

const rx = {
	header: /^( \* Version: )(.+?)\s*$/m,
	fork: /(define\(\s*'AI1WMNE_FORK_VERSION',\s*')([^']*)('\s*\);)/,
	upstream: /(define\(\s*'AI1WMNE_UPSTREAM_VERSION',\s*')([^']*)('\s*\);)/,
	reviewed: /(define\(\s*'AI1WMNE_MAX_REVIEWED_AI1WM_VERSION',\s*')([^']*)('\s*\);)/,
	stable: /^(Stable tag: )(.+?)\s*$/m,
};

function match(text, regex, label) {
	const m = text.match(regex);
	if (!m) {
		throw new Error(`cannot find ${label}`);
	}
	return m;
}

function declared() {
	return {
		plugin_header: match(read(PLUGIN), rx.header, 'plugin header Version')[2],
		AI1WMNE_FORK_VERSION: match(read('constants.php'), rx.fork, 'AI1WMNE_FORK_VERSION')[2],
		VERSION_file: read('VERSION').trim(),
		readme_stable_tag: match(read('readme.txt'), rx.stable, 'readme Stable tag')[2],
	};
}

function parse(version) {
	const m = SEMVER.exec(version);
	if (!m) {
		throw new Error(`version must be X.Y.Z, got "${version}"`);
	}
	return m.slice(1).map(Number);
}

function compare(a, b) {
	const [x, y] = [parse(a), parse(b)];
	for (let i = 0; i < 3; i++) {
		if (x[i] !== y[i]) {
			return x[i] < y[i] ? -1 : 1;
		}
	}
	return 0;
}

function check(expected) {
	const values = declared();
	const distinct = new Set(Object.values(values));
	if (expected) {
		distinct.add(expected);
	}
	if (distinct.size !== 1) {
		throw new Error(`version mismatch: ${JSON.stringify({ ...values, expected })}`);
	}
	return [...distinct][0];
}

function replaceIn(name, regex, value, label) {
	const text = read(name);
	match(text, regex, label);
	writeFileSync(file(name), text.replace(regex, (_, pre, _old, post) => `${pre}${value}${typeof post === 'string' ? post : ''}`));
}

function bump(version, { notes, upstream, reviewed }) {
	const current = check();
	if (compare(version, current) <= 0) {
		throw new Error(`new version ${version} must be greater than ${current}`);
	}
	if (!notes.length) {
		throw new Error('at least one --note is required');
	}

	replaceIn(PLUGIN, rx.header, version, 'plugin header Version');
	replaceIn('constants.php', rx.fork, version, 'AI1WMNE_FORK_VERSION');
	if (upstream) {
		replaceIn('constants.php', rx.upstream, upstream, 'AI1WMNE_UPSTREAM_VERSION');
	}
	if (reviewed) {
		replaceIn('constants.php', rx.reviewed, reviewed, 'AI1WMNE_MAX_REVIEWED_AI1WM_VERSION');
	}
	writeFileSync(file('VERSION'), `${version}\n`);
	replaceIn('readme.txt', rx.stable, version, 'readme Stable tag');

	const bullets = notes.map((note) => `- ${note}`).join('\n');
	const changelog = read('CHANGELOG.md');
	const heading = '# Changelog\n\n';
	if (!changelog.startsWith(heading)) {
		throw new Error('CHANGELOG.md must start with "# Changelog"');
	}
	writeFileSync(file('CHANGELOG.md'), `${heading}## ${version}\n\n${bullets}\n\n${changelog.slice(heading.length)}`);

	const readme = read('readme.txt');
	const marker = '== Changelog ==\n\n';
	if (!readme.includes(marker)) {
		throw new Error('readme.txt is missing "== Changelog =="');
	}
	const readmeBullets = notes.map((note) => `* ${note}`).join('\n');
	writeFileSync(file('readme.txt'), readme.replace(marker, `${marker}= ${version} =\n${readmeBullets}\n\n`));

	check(version);
}

function notes(version) {
	const lines = read('CHANGELOG.md').split('\n');
	const start = lines.findIndex((line) => line.trim() === `## ${version}`);
	if (start < 0) {
		throw new Error(`CHANGELOG.md has no section for ${version}`);
	}
	let end = lines.findIndex((line, i) => i > start && line.startsWith('## '));
	if (end < 0) {
		end = lines.length;
	}
	return lines.slice(start + 1, end).join('\n').trim();
}

function main(argv) {
	const [command, ...rest] = argv;
	switch (command) {
		case 'show':
			console.log(JSON.stringify(declared(), null, 2));
			return;
		case 'check':
			console.log(check(rest[0]));
			return;
		case 'notes':
			console.log(notes(rest[0]));
			return;
		case 'bump': {
			const version = rest[0];
			parse(version);
			const options = { notes: [], upstream: null, reviewed: null };
			for (let i = 1; i < rest.length; i += 2) {
				const value = rest[i + 1];
				if (value === undefined) {
					throw new Error(`${rest[i]} needs a value`);
				}
				if (rest[i] === '--note') {
					options.notes.push(value);
				} else if (rest[i] === '--upstream') {
					options.upstream = value;
				} else if (rest[i] === '--base-reviewed') {
					options.reviewed = value;
				} else {
					throw new Error(`unknown option ${rest[i]}`);
				}
			}
			bump(version, options);
			console.log(version);
			return;
		}
		default:
			throw new Error('usage: version.mjs show | check [v] | bump <v> --note ... | notes <v>');
	}
}

try {
	main(process.argv.slice(2));
} catch (error) {
	console.error(`error: ${error.message}`);
	process.exit(1);
}
