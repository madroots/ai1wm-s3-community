#!/usr/bin/env bash
# One-time repository configuration for the automated flow. Requires an admin `gh` login.
set -Eeuo pipefail

repo="${1:-$(gh repo view --json nameWithOwner --jq .nameWithOwner)}"

gh api -X PATCH "repos/${repo}" -F allow_auto_merge=true -F delete_branch_on_merge=true >/dev/null

# No bypass actors on purpose: an admin-bypassing PAT would make `gh pr merge --auto`
# merge immediately instead of waiting for the required checks.
gh api -X POST "repos/${repo}/rulesets" --input - <<'JSON'
{
  "name": "main: PR and green checks",
  "target": "branch",
  "enforcement": "active",
  "conditions": { "ref_name": { "include": ["~DEFAULT_BRANCH"], "exclude": [] } },
  "rules": [
    { "type": "deletion" },
    { "type": "non_fast_forward" },
    {
      "type": "pull_request",
      "parameters": {
        "required_approving_review_count": 0,
        "dismiss_stale_reviews_on_push": false,
        "require_code_owner_review": false,
        "require_last_push_approval": false,
        "required_review_thread_resolution": false
      }
    },
    {
      "type": "required_status_checks",
      "parameters": {
        "strict_required_status_checks_policy": false,
        "required_status_checks": [{ "context": "Required checks" }]
      }
    }
  ]
}
JSON
echo "Configured ${repo}."
