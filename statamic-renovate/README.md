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

## Inputs

- `token` (required): Gitea personal access token.
- `endpoint`: Gitea API endpoint. Defaults to
  `https://git.statamic.finetic.dev/api/v1`.
- `repository`: Repository in `owner/name` format. Defaults to
  `${{ github.repository }}`.
- `log_level`: Renovate log level. Defaults to `info`.
- `onboarding`: Whether to open an onboarding pull request. Defaults to `true`.
- `require_config`: Behavior when no config exists. Defaults to `optional`.
- `onboarding_config`: JSON used in the onboarding pull request. Defaults to
  `{"extends":["config:recommended"]}`.
