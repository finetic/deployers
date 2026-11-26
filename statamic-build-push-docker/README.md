# Statamic Build & Push

After checkout, the build generates `security.txt` using Actions variables.
Set `SECURITY_TXT_PATH=public/.well-known/security.txt` as an organization
variable on the platform that runs the workflow. A repository variable of the
same name overrides the path for that project. The path is relative to the
project workspace and must point to a file included in the image and served at
`/.well-known/security.txt`.

Set at least one of `SECURITY_TXT_EMAIL` and `SECURITY_TXT_PHONE` and give the
project access if using organization variables.
The file expires one calendar year after the build in UTC. Both build modes
include it through the local project context. Workflows with their own build step
can use `finetic/deployers/statamic-security-txt@main` after checkout.

Without `bake_file`, this action retains its existing single-image build, tags and
registry-cache behavior. `site_name` selects that image and cache name.

Set `bake_file` to a local HCL/Compose Bake definition to build multiple images in
one BuildKit graph. The default Bake group is built and pushed. The file owns its
contexts, Dockerfiles, targets, site-to-theme mapping, image names, platforms and
cache configuration. Bake receives these environment variables:

| Variable | Value |
| --- | --- |
| REGISTRY | `inputs.registry` |
| IMAGE_TAG | `inputs.image_tag` |
| GIT_REVISION | `github.sha` |
| COMPOSER_AUTH | `inputs.composer_auth`, for an env-backed BuildKit secret |

Example consumer additions:

```yaml
with:
  # Existing credentials/registry/tag inputs still apply.
  bake_file: docker/docker-bake.hcl
```

The Bake file should reference Composer auth as
`type=env,id=composer_auth,env=COMPOSER_AUTH`. Never pass credentials as build args.
`site_name` remains an input for compatibility; Bake defines its own image names.
Only enable this input in consumers after this action version has been published.
