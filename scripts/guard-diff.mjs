#!/usr/bin/env node
// Rejects an AI-authored change before it is pushed.
//
//   node scripts/guard-diff.mjs <base-ref>
//
// The agent edits source files only. Anything that could widen its reach (the
// update channel, CI, the tests that judge it) or hide a payload is refused.
// The workflow runs a pristine copy of this file taken from the base ref, so the
// agent cannot loosen it.

import { execFileSync } from 'node:child_process';

const base = process.argv[2];
if (!base) {
	console.error('usage: guard-diff.mjs <base-ref>');
	process.exit(1);
}

const git = (...args) => execFileSync('git', args, { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 });

const problems = [];

// 1. Paths. Existing tests may not be touched; new regression cases may be added.
const status = git('diff', '--name-status', '--no-renames', base).split('\n').filter(Boolean);
for (const line of status) {
	const [code, path] = line.split('\t');
	const forbidden =
		path.startsWith('.github/') ||
		path.startsWith('scripts/') ||
		path === 'LICENSE' ||
		(path.startsWith('tests/') && !(code === 'A' && /^tests\/cases\/[\w.-]+\.sh$/.test(path)));
	if (forbidden) {
		problems.push(`forbidden path change: ${code} ${path}`);
	}
}

// 2. Content. Both added and removed lines are inspected, so deleting a safety
//    check is as visible as adding a dangerous call.
const patch = git('diff', '-U0', '--no-color', '--no-renames', base, '--', '*.php', '*.js');
const SENSITIVE_TOKENS = /AI1WMNE_GITHUB_|AI1WMNE_PLUGIN_CHECK|api\.github\.com|pre_http_request|site_transient_update_plugins|plugins_api|Update URI/;
const DANGEROUS_CALLS = /\b(eval|assert|create_function|shell_exec|exec|system|passthru|proc_open|popen|pcntl_exec|base64_decode|gzinflate|str_rot13)\s*\(|`[^`]*\$[^`]*`/;
const ALLOWED_HOSTS = new Set([
	'github.com',
	'www.gnu.org',
	'wordpress.org',
	'api.wordpress.org',
	'downloads.wordpress.org',
	'best-web.sk',
	'servmask.com',
	'amazonaws.com',
	'www.w3.org',
	'schemas.xmlsoap.org',
	'aws.amazon.com',
	'docs.aws.amazon.com',
	'example.com',
]);

let file = '';
for (const line of patch.split('\n')) {
	if (line.startsWith('+++ b/') || line.startsWith('--- a/')) {
		file = line.slice(6);
		continue;
	}
	if (!/^[+-]/.test(line) || line.startsWith('+++') || line.startsWith('---')) {
		continue;
	}
	const text = line.slice(1);
	const where = `${file}: ${line.slice(0, 120)}`;
	if (SENSITIVE_TOKENS.test(text)) {
		problems.push(`touches update channel / HTTP interception: ${where}`);
	}
	if (line.startsWith('+')) {
		if (DANGEROUS_CALLS.test(text)) {
			problems.push(`dangerous call added: ${where}`);
		}
		for (const [, host] of text.matchAll(/https?:\/\/([a-z0-9.-]+)/gi)) {
			const known = [...ALLOWED_HOSTS].some((allowed) => host === allowed || host.endsWith(`.${allowed}`));
			if (!known) {
				problems.push(`new network host ${host}: ${where}`);
			}
		}
	}
}

// 3. Size sanity. The first catch-up run is large; a runaway rewrite is not.
const stat = git('diff', '--shortstat', base).trim();
const changed = /(\d+) files? changed/.exec(stat);
if (changed && Number(changed[1]) > 60) {
	problems.push(`too many files changed: ${stat}`);
}

if (problems.length) {
	console.error(`guard-diff: ${problems.length} problem(s)\n${problems.map((p) => `  - ${p}`).join('\n')}`);
	process.exit(1);
}
console.log(`guard-diff: ok (${status.length} files)`);
