#!/usr/bin/env bash
# The base plugin must accept this extension as current: Ai1wm_Compatibility
# only skips its version gate for the exact 'develop' marker.
set -Eeuo pipefail
source /tests/lib.sh

result="$(wp eval '
$ext = Ai1wm_Extensions::get();
$name = AI1WMNE_PLUGIN_NAME;
echo isset($ext[$name]) ? "registered" : "missing", "|";
echo isset($ext[$name]) ? $ext[$name]["version"] : "", "|";
echo isset($ext[$name]) && Ai1wm_Compatibility::check($ext[$name]) ? "compatible" : "INCOMPATIBLE", "|";
echo count(Ai1wm_Compatibility::get(array($ext[$name]["short"] => 1)));
')"

IFS='|' read -r registered version compatible messages <<<"${result}"
assert_eq registered "${registered}" 'extension is registered with the base plugin'
assert_eq develop "${version}" "extension reports the 'develop' marker"
assert_eq compatible "${compatible}" 'base plugin compatibility check passes'
assert_eq 0 "${messages}" 'base plugin raises no out-of-date message'
