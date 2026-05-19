# Tests

| Suite | Config | Bootstrap |
|-------|--------|-----------|
| Unit | `phpunit.unit.xml` | TYPO3 Testing Framework Unit bootstrap |
| Functional | `phpunit.functional.xml` | TYPO3 Testing Framework Functional bootstrap |
| E2E | `playwright.config.ts` | DDEV demo instance (`/semantilizer-demo`) |

## Run locally

```bash
ddev start
ddev composer install
ddev test              # unit + functional
ddev test --unit
ddev test --functional
```

E2E (requires initialized demo):

```bash
ddev init
npm install
npm run test:e2e:install   # once
npm run test:e2e
```

Or without DDEV for unit tests only:

```bash
composer test:unit
composer test:functional   # needs typo3Database* env vars
composer test:all
```

## Test coverage

### Unit

- `AbstractHeadlineViewHelperTest` — `edit` parsing, semantic markup, relations
- `HeadlineViewHelperTest` — rendering and relation fallback
- `RelationViewHelperTest` — child (+1) and sibling (mirror) behaviour
- `AbstractMiddlewareTest` / `RequestMiddlewareTest` — `X-Semantilizer` header and cache disabling
- `ExtensionConfigurationTest` — composer/emconf/services smoke checks

### Functional

- `SemantilizerDataTest` — `header_type` DB field and schema
- `SemantilizerTcaTest` — TCA column and palette
- `SemantilizerConfigurationTest` — middleware and JS module registration
- `SemantilizerFrontendTest` — FSC + Semantilizer partial rendering (h1–h3, non-semantic div)

### E2E

- `e2e/demo-semantilizer.spec.ts` — demo page headline hierarchy and CSS classes

## Fixtures

Functional tests import `Tests/Functional/Fixtures/Database/semantilizer.csv`.

Demo content for manual testing is created via `ddev seed` (see `Build/seed-config.yaml`).

## Functional tests without DDEV

| Variable | DDEV value |
|----------|------------|
| `typo3DatabaseHost` | `db` |
| `typo3DatabaseName` | `db` |
| `typo3DatabaseUsername` | `root` |
| `typo3DatabasePassword` | `root` |
| `typo3DatabaseDriver` | `mysqli` |
| `typo3DatabasePort` | `3306` |
