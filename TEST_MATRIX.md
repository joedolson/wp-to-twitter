# Status Update Behavior Matrix

This matrix documents expected publish versus edit behavior for status updates in XPoster.

The current classifier prefers explicit status transition data when available and falls back to timestamp heuristics only for legacy workflows that do not provide prior post state.

## Decision Rules

1. `future` publishing context is always treated as a first publish.
2. If the prior post object is available:
   - non-`publish` -> `publish` is treated as a first publish.
   - `publish` -> `publish` is treated as an edit.
3. If prior state is unavailable, use `wpt_post_is_new()` as fallback.
4. For classic editor backdated first publish, `edit_date=1` without `save` is treated as a first publish.

## Matrix

| Scenario | Hook / path | Prior state available | Expected classification | Expected setting gate | Notes |
| --- | --- | --- | --- | --- | --- |
| Classic editor first publish | `wp_after_insert_post` -> `wpt_do_post_update()` | Yes | Publish | `post-published-update` | Draft, pending, future, or private moving to published should send only if publish updates are enabled or the one-time override is `yes`. |
| Classic editor edit of published post | `wp_after_insert_post` -> `wpt_do_post_update()` | Yes | Edit | `post-edited-update` | A published post updated and saved again should not use publish settings. |
| Block editor first publish | `wp_after_insert_post` -> `wpt_do_post_update()` | Yes | Publish | `post-published-update` | Same behavior as classic editor; status transition data should prevent timestamp-based misclassification. |
| Block editor edit of published post | `wp_after_insert_post` -> `wpt_do_post_update()` | Yes | Edit | `post-edited-update` | Editing published content should not post when edit updates are disabled. |
| Scheduled post reaches publish time | `future_to_publish` -> `wpt_post_update_future()` | Not needed | Publish | `post-published-update` | Scheduled publication is always a first publish. |
| Scheduled post edited before publish time | `wp_after_insert_post` on save | Yes | Edit | `post-edited-update` | Editing a scheduled post before it publishes should not trigger a publish update. |
| Previously scheduled post edited after publication | `wp_after_insert_post` -> `wpt_do_post_update()` | Yes | Edit | `post-edited-update` | Once already published, later saves are edits. |
| Backdated first publish from classic editor | `wp_after_insert_post` with `edit_date=1` | Usually yes | Publish | `post-published-update` | Fallback POST check exists for older flows where timestamps alone may look like an edit. |
| Draft created with past date, then later published | Varies by editor flow | Sometimes | Publish | `post-published-update` | Expected behavior is publish, but this remains a known weak area when prior state is unavailable and legacy fields are missing. |
| Bulk edit on published posts | `bulk_edit_posts` -> `wpt_post_update()` | No | Edit | `post-edited-update` | Expected behavior is edit, but current implementation still falls back to timestamps because bulk edit does not pass prior post state. |
| Bulk edit that changes status to publish | Not directly covered in current bulk path | No | Publish | `post-published-update` | If a workflow publishes via bulk operation, classification may depend on fallback heuristics and should be treated as a risk case. |
| XML-RPC first publish | `xmlrpc_publish_post` -> `wpt_post_update()` | No | Publish | `post-published-update` | Expected behavior relies on fallback heuristics because prior post state is not passed. |
| XML-RPC edit of published post | `xmlrpc_publish_post` -> `wpt_post_update()` | No | Edit | `post-edited-update` | Expected behavior relies on fallback heuristics and remains a legacy risk area. |

## Override Expectations

1. `_wpt_post_this = yes` is a one-time override for the current save.
2. `_wpt_post_this = no` suppresses posting for the current save.
3. After a successful send, `_wpt_post_this` should be deleted.
4. A persisted `_wpt_post_this` value should never cause repeated posting across later edits.

## Regression Priorities

1. Published post edited with `post-edited-update` disabled must not send.
2. Draft or scheduled post published for the first time must use publish settings, not edit settings.
3. Block editor and classic editor should classify the same state transition identically.
4. XML-RPC and bulk edit should be tested separately because they still depend on fallback classification.