# Statamic deployment workflows with visible jobs

Gitea can display a reusable-workflow call as a single job with its work under
`Set up job`. These opt-in templates declare native jobs in the consuming project
and use shared composite actions for execution. Backend checks, frontend checks,
configuration validation, image build, site deployments, release and SBOM uploads
therefore have separate job statuses and logs in the run.

## Gradual migration

1. Publish `dependencytrack/composer`, `dependencytrack/npm` and
   `statamic-release` in `finetic/deployers` on `main` **before** updating consumers.
   Workflow templates reference that branch.
2. Copy `deploy-acceptance.yml` and `deploy-production.yml` from this directory to
   the project's `.github/workflows/`. Preserve project-specific PHP/Node versions,
   frontend directories, registry, server and any disabled checks when migrating.
3. Keep `CLIENT_NAME` and `SITE_NAME` variables. Replace `site-name` in all four
   literal `strategy.matrix.site` lists with the actual deployment sites. Keep
   `notify-started`, `deploy`, `notify-completed` and `notify-failed` lists equal.
   On Gitea 1.26.4, the job parser does not evaluate a dynamic
   `fromJSON(vars.DEPLOY_SITES ...)` matrix before expansion; the resulting
   `matrix.site` is empty. DEPLOY_SITES can remain for legacy consumers, but these
   templates intentionally use literal YAML lists. Do not replace them with
   expressions based on variables or job outputs on this server version.
4. Preserve secrets `COMPOSER_AUTH`, `NEXUS_DOCKER_USERNAME`,
   `NEXUS_DOCKER_PASSWORD`, `DEPLOYMENT_PRIVATE_SSH_KEY` and optional
   `SLACK_WEBHOOK_URL`. Production release also uses `GIT_USERNAME`, `GIT_EMAIL`
   and a repository token with contents-write permission. As in the existing
   workflow, `GIT_PRIVATE_SSH_KEY` overrides `github.token`; despite the legacy
   name, the checkout token input expects an HTTP access token, not an SSH key.
5. For production SBOM uploads, provide variables `DEPENDENCY_TRACK_HOSTNAME`
   (without protocol), `DEPENDENCY_TRACK_PROJECT_UUID_BACKEND`,
   `DEPENDENCY_TRACK_PROJECT_UUID_FRONTEND` and secret `DEPENDENCY_TRACK_API_KEY`.
   These projects must already exist. Remove the project's standalone
   `dependencytrack.yml` in the same change to avoid duplicate uploads.
6. Apply the acceptance workflow on the acceptance branch and the production
   workflow on main through the project's normal review process. Verify a real
   acceptance run before migrating the next project.

Existing `statamic-project-acceptance.yml`, `statamic-project-production.yml`,
`versioning.yml` and `dependencytrack-*.yaml` entrypoints remain unchanged.
Projects can migrate independently without switching every consumer at once.
The pull-request workflow is independent and is not changed by these templates.

## Execution and failures

Acceptance retains the `acceptance` image tag, `acc` environment and acceptance
server; production retains the `production` tag, `prod` environment and production
server. Site deployment matrices and concurrency groups are preserved.

SBOM uploads run on production (`main`, including manual production runs), as in
the old standalone flow. Acceptance does not overwrite the production SBOMs.
For acceptance SBOM publication, use separate Dependency-Track projects and add
explicit jobs referencing their UUIDs.

Production SBOM jobs start after successful checks and deployment validation and
run alongside the image build. An upload failure marks the run failed and is
included in the failure notification; it does not roll back or block deployment.
The success notification waits for deployment, release and both uploads.
Acceptance success waits for deployment. Slack delivery remains best effort.

## Per-site notifications

`notify-started`, `notify-completed` and `notify-failed` use the same site matrix
as `deploy`, using identical explicit YAML lists.
Each job name and Slack message identifies its site. Notification matrices use
`fail-fast: false` so a job-level failure does not cancel other sites' messages.

Completion waits for the entire deployment matrix (and, in production, release
and both SBOM uploads). `needs.deploy.result` is the aggregate matrix result,
not an individual site's result. A failed-flow message therefore says that the
flow contains errors and labels deployment failure as the combined result of
all sites. It does not claim which particular site failed. Shared check, build,
release or SBOM failures are reported to every affected site's notification.

The shared deploy action rejects an empty or whitespace-only site before SSH
setup, preventing a missing matrix value from reaching a target such as `-acc`.
Source for the Gitea 1.26 parser behavior:
https://github.com/go-gitea/gitea/blob/v1.26.4/modules/actions/jobparser/jobparser.go
