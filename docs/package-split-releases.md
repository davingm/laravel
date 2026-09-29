# Composer package split releases

This repository keeps Composer packages under `packages/` and publishes each package to `github.com/davingm/<folder-name>`. The release workflow uses `git subtree split`, which creates a package-only history while retaining the history of files under that package path.

## Current package folders

`packages/auth` is currently the only package folder with a `composer.json`; its Composer name must remain `davingm/auth`. `packages/dev` is an empty placeholder and is ignored by the workflow until it contains a Composer manifest. There is no `packages/laravel` directory yet, so a `laravel-v...` release tag will fail validation. Add a package directory and its manifest before releasing it; the manifest name must be `davingm/<directory-name>`.

The root project remains the framework's `davingm/laravel` create-project source. This checkout's GitHub origin is also `davingm/laravel`, so that repository cannot be used as a split destination from itself. If you later add `packages/laravel` and want to publish it as `davingm/laravel`, first move the monorepo to a different GitHub repository; the workflow explicitly blocks a self-push. The split workflow only publishes directories under `packages/`; it does not move or split the root project.

## Prepare destination repositories

Create one separate GitHub repository for every package using the exact Composer vendor and folder name. For example, `packages/auth` publishes to `davingm/auth`. Set each destination repository's default branch to `main` and leave it empty before its first release. An existing unrelated README commit creates a different history and causes the safe, non-force push to fail.

The package manifest name is checked during release. For an auth package, `packages/auth/composer.json` must contain:

```json
{
  "name": "davingm/auth"
}
```

Do not put credentials in package manifests or workflow files.

## Create the publishing token and secret

Create a GitHub fine-grained personal access token for the account or organization that owns the destination repositories. Grant access only to the package destination repositories and grant **Contents: Read and write**. The workflow uses the token only for pushes to those repositories.

In the monorepo repository, open **Settings → Secrets and variables → Actions → New repository secret** and add:

- Name: `MONOREPO_SPLIT_TOKEN`
- Secret: the token you created

The workflow checkout uses the read-only `GITHUB_TOKEN`; `MONOREPO_SPLIT_TOKEN` is used only for publishing to the separate repositories. Rotate or revoke it in GitHub if it is exposed.

## Release one package

Commit the changes to the package in the monorepo, then create and push a package-prefixed SemVer tag from that commit:

```bash
git tag auth-v1.0.0
git push origin auth-v1.0.0
```

The workflow parses the folder name and version from the tag, checks that the matching package manifest exists and has the expected `davingm/<folder>` name, splits only that folder, then atomically pushes the package history to the destination's `main` branch with the clean `v1.0.0` tag. Other packages and root files are not pushed.

Examples:

| Monorepo tag | Split directory | Destination | Destination tag |
| --- | --- | --- | --- |
| `auth-v1.0.0` | `packages/auth` | `davingm/auth` | `v1.0.0` |
| `support-v1.0.0` | `packages/support` | `davingm/support` | `v1.0.0` |
| `laravel-v1.0.0` | `packages/laravel` | `davingm/laravel` | `v1.0.0` |

Only the package named in the pushed tag is published. The version format is `name-vX.Y.Z`; the workflow rejects malformed versions, missing package folders, name mismatches, or a missing token. It does not force-push, so a non-fast-forward destination or an existing release tag fails without overwriting destination history.

## Verify a release

Open the **Actions** tab in the monorepo and inspect the **Split Composer package** run. A successful run includes the package name, split commit, source tag, and clean destination tag in its summary. Then check the destination repository's `main` branch and `vX.Y.Z` tag.

The workflow only reads the monorepo and pushes to the selected destination repository. It does not checkout another branch, rewrite monorepo history, or delete or modify files in the source repository.
