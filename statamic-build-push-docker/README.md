# Statamic Build & Push

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
