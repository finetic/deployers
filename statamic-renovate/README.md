# Statamic Renovate

Runs Renovate for a single Statamic repository hosted on the self-hosted
Finetic Gitea instance. The action targets the repository that started the
workflow and uses `https://git.statamic.finetic.dev/api/v1`.

A composite action cannot schedule itself. Add this workflow as
`.gitea/workflows/renovate.yml` to every Statamic project:

```yaml
---
name: Renovate

on:
  schedule:
    - cron: "17 3 * * *"
  workflow_dispatch:

jobs:
  renovate:
    runs-on: ubuntu-latest
    steps:
      - name: Run Renovate
        uses: https://github.com/finetic/deployers/statamic-renovate@main
        with:
          token: ${{ secrets.RENOVATE_TOKEN }}
          github_token: ${{ secrets.RENOVATE_GH_TOKEN }}
          composer_auth: ${{ secrets.COMPOSER_AUTH }}
```

Create `RENOVATE_TOKEN` as an Actions secret. It should be a personal access
token for a dedicated Gitea bot account with read/write access to the
repository, user read access, and issue read/write access. The bot account
must have a full name and email address. Renovate pushes its branches and
creates its pull requests on `https://git.statamic.finetic.dev/`.

The self-hosted runner must be able to pull `renovate/renovate:43` from Docker
Hub and connect to the Gitea instance and the dependency registries used by the
project.

By default Renovate opens an onboarding pull request containing
`config:recommended`. After that pull request is merged, Renovate maintains
Composer, npm and other detected dependencies according to the generated
`renovate.json`.

`COMPOSER_AUTH` must be a complete Composer `auth.json` JSON object. For
Packeton with HTTP Basic authentication, store this as an Actions secret:

```json
{"http-basic":{"packeton.finetic.dev":{"username":"...","password":"..."}}}
```

Do not add this value to `renovate.json` or the repository. The action derives
masked Renovate `hostRules` from it, passes the original object to Composer for
lockfile updates, and verifies both Packeton Composer repository endpoints
before Renovate starts.

## Inputs

- `token` (required): Gitea personal access token.
- `endpoint`: Gitea API endpoint. Defaults to
  `https://git.statamic.finetic.dev/api/v1`.
- `repository`: Repository in `owner/name` format. Defaults to
  `${{ github.repository }}`.
- `log_level`: Renovate log level. Defaults to `debug`.
- `enabled_managers`: JSON array of enabled managers. Composer is required and
  enabled by default alongside npm, Docker and GitHub Actions managers.
- `onboarding`: Whether to open an onboarding pull request. Defaults to `true`.
- `require_config`: Behavior when no config exists. Defaults to `optional`.
- `onboarding_config`: JSON used in the onboarding pull request. Defaults to
  `{"extends":["config:recommended"]}`.
- `composer_auth`: complete Composer `auth.json`, supplied through a secret.
- `composer_registry`: Packeton base URL used by the metadata preflight.
