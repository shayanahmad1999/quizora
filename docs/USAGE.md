# Usage walkthrough

## Example: a five-question physics practice quiz

Sign in as the first super administrator. In **Categories**, create `Physics`, choose a category color, and mark it active. With demo data installed, Physics already exists with eight example questions.

In **Question bank**, add questions using a prompt, four choices, exactly one correct choice, an explanation, a difficulty, and an active state. For example:

| Field | Example |
| --- | --- |
| Category | Physics |
| Prompt | What is the SI unit of force? |
| Choice A | Joule |
| Choice B | Newton |
| Choice C | Watt |
| Choice D | Pascal |
| Correct choice | B |
| Explanation | Force is measured in newtons. |
| Difficulty | Easy |

Enter at least five active questions. In **Quiz studio**, create `Physics - First Practice`, select Physics, request five questions, allow ten minutes, set a 60% passing threshold, and allow three attempts. Enable question and option shuffling. Choose whether to show answers after completion. Select **All learners**, or choose **Assigned learners** and explicitly select the eligible accounts. Publish the quiz.

Publishing fails when the active question bank is too small. Deactivating questions later can also make new attempts unavailable; add enough active questions or lower the requested count.

## Create the learner account

In **People**, add a learner named `Practice Learner` with a unique email and a temporary password. Do not reuse these example names or any shared test password for real production accounts. Keep the role as **Learner** and account active.

For a consistent appearance, choose **Always use the assigned theme** and select **Meadow**. For a changing experience, choose **Random theme at each login**, then select an allowed subset such as Ocean, Amethyst, and Midnight. A blank allowed pool means all active themes.

Send the temporary credentials through a private channel. The learner logs in, replaces the temporary password, and opens **Explore quizzes**. Theme selection is controlled by the administrator; the learner cannot change permissions or theme assignments from the browser payload.

## Take the quiz

Starting the quiz fixes the server deadline and snapshots the question text, answer choices, correct choices, explanations, and important quiz settings. Clicking Start twice while the same attempt remains active returns that existing attempt instead of consuming a second attempt.

Each answer selection saves automatically. Use **Save answer** to retry after a connection or validation failure. Rapid changes are queued in order. Next/previous navigation waits for the save queue. An unanswered option clears a previously selected answer.

The question-number grid permits navigation within the attempt. **Finish & submit** finalizes the saved answers after confirmation. Unanswered questions receive zero marks. Answers cannot be changed after finalization.

The displayed countdown is an interface aid. The server decides whether an answer is on time. Changing the local clock, reloading, or closing the desktop window does not extend the server deadline. Keep the application server clock synchronized.

## Understand the result

Five questions means five possible marks. With three correct answers, the displayed percentage is 60% and a 60% threshold is passed. There is no negative marking. The pass comparison uses the exact fraction, not the rounded displayed percentage.

When answer review is enabled, the result page shows the saved question text, the learner's choice, the correct choice, and the explanation. When disabled, learners receive their score and summary without the answer key. Administrators can inspect results for oversight.

Editing a bank question later does not rewrite historical attempts. Editing duration, pass threshold, title, category name, or review behavior on a quiz does not rewrite the settings already snapshotted into an existing attempt.

Unpublishing or reassigning a quiz affects new availability; it does not cancel an already-started attempt. Disable the learner account to revoke its access on subsequent requests.

An expired attempt is counted toward the attempt limit. A Start click encountering an old unfinished attempt first returns that now-finalized attempt; the learner may return to the catalog and start another attempt if their limit allows it.

## Review and manage

Use **Reports** to view attempts and filter by quiz, status, or learner name/email. CSV export uses the current relevant filters and returns all matching records, rather than only the visible page. Export timestamps are explicitly labeled UTC.

Use **People** to disable access or reset a password. New or reset passwords require a change at the next sign-in. Account edits revoke older sessions through a server-side version check; a request already in flight is not a substitute for a live connection kill switch.

Deletion of users, questions, and quizzes is soft deletion, retaining historical associations. A category with live questions/quizzes cannot be deleted; deactivate it instead. This is archival behavior, not legal data erasure. Privacy/retention deletion requires an explicit administrative retention process.

The activity log records selected management changes and attempt lifecycle events. It is not a tamper-proof security ledger, full field-diff history, or complete authentication-event log.

## Customize appearance

In **Appearance**, create or edit a theme. Choose Sidebar, Topbar, or Focus layout and valid six-digit hex colors. Check readability and contrast in both desktop and mobile browser sizes before assigning a custom palette widely.

The default theme cannot be disabled/deleted until Settings points to another active theme. A fixed theme in use must be reassigned first. At least one active theme must remain. Random-login themes stay consistent while the session is active; changes to available themes may force a new valid fallback.

Changing a user's theme is not a unique-design generator. Several users may share one palette or layout. Create enough themed presets and assign them explicitly when distinct appearances are important.
