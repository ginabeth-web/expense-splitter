# Expense Splitter Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A web app where signed-in users create groups, log shared expenses split equally, by exact amounts, or by percentage, see who owes whom, and record settle-up payments.

**Architecture:** Laravel 12 backend with a Vue 3 frontend, joined by Inertia.js (Laravel routes and controllers return Vue pages instead of Blade views, so no separate API is needed), built on the existing skeleton. The money logic (split calculation, balances, debt simplification) lives in small plain-PHP service classes under `app/Services` with unit tests; controllers stay thin, enforce access with a `GroupPolicy`, and pass page data to Vue as Inertia props. All money is stored and computed as integer cents.

**Tech Stack:** PHP 8.2+, Laravel 12, Laravel Breeze (Vue + Inertia stack, plain JavaScript) for auth and scaffolding, Vue 3, Inertia.js, Tailwind + Vite, SQLite, PHPUnit 11.

**Spec:** None. The user skipped the design conversation; the scope below is the planner's call and is the spec this plan argues from.

## Scope (planner's calls — review these)

- **Users:** everyone signs up with email and password (Breeze). No social login, no guest/unregistered members.
- **Groups:** any user can create a group (name + 3-letter currency code, default `PHP`). The creator is a member. Members add other *registered* users by email. No invitation emails.
- **Expenses:** description, amount, date, who paid (one member), and which members share it. Split types: `equal`, `exact`, `percent`. Any group member can add, edit, or delete any expense in the group.
- **Balances:** per group, each member's net balance (positive = is owed money), plus a simplified "who pays whom" list with the fewest practical transfers.
- **Settlements:** a member records "A paid B ₱X" inside a group; it moves balances like an expense does.
- **Dashboard:** lists your groups with your net balance in each.
- **Removing a member:** allowed only when their balance in that group is exactly zero.
- **Frontend:** Vue 3 through Inertia (user asked for Vue; Inertia over a separate SPA + JSON API is the planner's call — one codebase, Laravel's session auth and validation errors flow straight into Vue forms). Plain JavaScript, not TypeScript.
- **Out of scope:** multi-currency conversion, receipts/attachments, notifications, recurring expenses, mobile app, public API.

## Global Constraints

- Money is stored as integer cents (`unsignedBigInteger` columns named `*_cents`); floats never touch money. User input is parsed from strings with `App\Support\Money::toCents()`.
- Every split's shares sum to exactly the expense amount.
- Only members of a group can see or change anything in it; non-members get HTTP 403.
- Tests use PHPUnit (Breeze installed without `--pest`) and `RefreshDatabase`. Page tests check Inertia props with `->assertInertia(fn (AssertableInertia $page) => $page->component('Groups/Show')->where(...))`, not `assertSee`.
- Money is formatted on the server: every money prop is sent as both `*_cents` (int) and `*_display` (string from `Money::format`). Vue components only display, never do money math or formatting, so there is no JS copy of the money rules.
- Vue pages live in `resources/js/Pages/<Area>/<Name>.vue` and use Breeze's `AuthenticatedLayout`; forms use Inertia's `useForm` and show `form.errors.<field>` with Breeze's `InputError` component.
- Run tests with `php artisan test`. The XAMPP PHP startup warnings about `pdo_firebird`/`pdo_oci`/`openssl` are environment noise, not failures.
- Format with `vendor/bin/pint` before each commit.

## Review Focus

1. **Amounts that don't divide evenly** (₱100.00 split 3 ways) — shares must be 3334/3333/3333 cents, summing to 10000, deterministic by user id. Tested in Task 3.
2. **Exact amounts or percentages that don't add up** (exact shares sum ≠ total, percents sum ≠ 100) — the form rejects with a validation error, nothing saved. Tested in Tasks 3 and 4.
3. **A non-member hitting group, expense, or settlement URLs directly** — 403, including editing an expense from another group via a mismatched URL. Tested in Tasks 2, 4, 6.
4. **A payer or participant who isn't in the group** (tampered form) — validation error. Tested in Task 4.
5. **Messy money input** (`"12.3"`, `"12.345"`, `"0"`, `"-5"`, `"1,000"`, `"abc"`) — `"12.3"` → 1230 cents; the rest rejected except `"1,000"` → 100000. Tested in Task 3.

---

## File Structure

| Path | Responsibility |
|------|----------------|
| `app/Support/Money.php` | Parse money strings to cents; format cents for display |
| `app/Services/SplitCalculator.php` | Turn amount + split type + inputs into per-user shares |
| `app/Services/BalanceCalculator.php` | Net balance per member from expenses, shares, settlements |
| `app/Services/DebtSimplifier.php` | Net balances → list of transfers |
| `app/Models/{Group,Expense,ExpenseShare,Settlement}.php` | Eloquent models |
| `app/Policies/GroupPolicy.php` | Membership-based access |
| `app/Http/Controllers/{Group,GroupMember,Expense,Settlement,Dashboard}Controller.php` | Thin controllers |
| `app/Http/Requests/{StoreGroupRequest,AddMemberRequest,ExpenseRequest,SettlementRequest}.php` | Validation |
| `resources/js/Pages/Groups/*.vue`, `resources/js/Pages/Expenses/*.vue`, `resources/js/Pages/Dashboard.vue` | Vue pages rendered by Inertia |
| `resources/js/Components/*.vue` | Reusable Vue pieces (expense form, balance list) |
| `routes/web.php` | Routes (all behind `auth`) |
| `tests/Unit/*`, `tests/Feature/*` | Tests |

---

### Task 1: Authentication and Vue/Inertia scaffolding with Breeze

**Files:**
- Modify: `composer.json`, `package.json`, `vite.config.js`, `routes/web.php`, `resources/js/app.js` (via installer)
- Create: Breeze's auth controllers, `app/Http/Middleware/HandleInertiaRequests.php`, `resources/views/app.blade.php`, `resources/js/Pages/**`, `resources/js/Layouts/**`, `resources/js/Components/**`, `tests/Feature/Auth/*` (via installer)

**Interfaces:**
- Produces: `auth` middleware-protected routes, `dashboard` named route, `AuthenticatedLayout.vue` used by every later page, `HandleInertiaRequests::share()` (later tasks add a `flash` prop here).

- [ ] **Step 1:** `composer require laravel/breeze --dev`
- [ ] **Step 2:** `php artisan breeze:install vue` (no SSR, no TypeScript, no ESLint, PHPUnit, no dark mode). Then `npm install && npm run build`. If the installer replaced Tailwind 4 with Tailwind 3, keep whatever it installed — the build passing is what matters.
- [ ] **Step 2b:** In `HandleInertiaRequests::share()` add `'flash' => ['success' => fn () => $request->session()->get('success')]`, and show it as a banner in `AuthenticatedLayout.vue`. Controllers in later tasks redirect `->with('success', '...')`.
- [ ] **Step 3:** `php artisan migrate`
- [ ] **Step 4:** Run `php artisan test` and `npm run build`. Expected: all Breeze auth tests PASS, build succeeds.
- [ ] **Step 5:** Commit: `git add -A && git commit -m "feat: add Breeze authentication with Vue and Inertia"`

---

### Task 2: Groups and membership

**Files:**
- Create: `database/migrations/xxxx_create_groups_table.php`, `database/migrations/xxxx_create_group_user_table.php`
- Create: `app/Models/Group.php`, `database/factories/GroupFactory.php`
- Modify: `app/Models/User.php` (add `groups()`)
- Create: `app/Policies/GroupPolicy.php`
- Create: `app/Http/Controllers/GroupController.php`, `app/Http/Controllers/GroupMemberController.php`
- Create: `app/Http/Requests/StoreGroupRequest.php`, `app/Http/Requests/AddMemberRequest.php`
- Create: `resources/js/Pages/Groups/Index.vue`, `Create.vue`, `Show.vue`
- Modify: `routes/web.php`, `resources/js/Layouts/AuthenticatedLayout.vue` (add "Groups" nav link)
- Test: `tests/Feature/GroupTest.php`

**Interfaces:**
- Produces:
  - `groups` table: `id, name (string 100), currency (char 3), created_by (FK users), timestamps`
  - `group_user` pivot: `group_id, user_id, timestamps`, unique `(group_id, user_id)`
  - `Group::members(): BelongsToMany<User>`, `Group::creator(): BelongsTo<User>`, `Group::hasMember(User $u): bool`
  - `User::groups(): BelongsToMany<Group>`
  - `GroupPolicy::view(User, Group)` and `GroupPolicy::update(User, Group)` — both `$group->hasMember($user)`
  - Routes: `groups.index` GET `/groups`, `groups.create` GET `/groups/create`, `groups.store` POST `/groups`, `groups.show` GET `/groups/{group}`, `groups.members.store` POST `/groups/{group}/members`
  - Inertia props: `Groups/Index` → `groups: [{id, name, currency}]`; `Groups/Show` → `group: {id, name, currency}`, `members: [{id, name, email}]`. Later tasks add props to `Groups/Show`.

- [ ] **Step 1: Write failing tests in `tests/Feature/GroupTest.php`**

```php
test_user_can_create_group_and_becomes_member()
    // POST groups.store ['name' => 'Baguio Trip', 'currency' => 'PHP']
    // assertRedirect(route('groups.show', $group)); $group->hasMember($user) === true; created_by === $user->id
test_group_name_is_required_and_currency_is_three_letters()
    // POST ['name' => '', 'currency' => 'PESO'] → assertSessionHasErrors(['name', 'currency'])
test_index_lists_only_my_groups()
    // two groups, user in one → assertInertia: component('Groups/Index'), has('groups', 1), where('groups.0.name', mine->name)
test_show_passes_group_and_members()
    // assertInertia: component('Groups/Show'), where('group.id', ...), has('members', 2)
test_non_member_cannot_view_group()
    // GET groups.show as outsider → assertForbidden()
test_member_can_add_registered_user_by_email()
    // POST groups.members.store ['email' => $friend->email] → $group->hasMember($friend)
test_adding_unknown_email_fails()
    // ['email' => 'nobody@example.com'] → assertSessionHasErrors('email')
test_adding_existing_member_fails()
    // → assertSessionHasErrors('email'), pivot count unchanged
test_non_member_cannot_add_members()
    // → assertForbidden()
```

- [ ] **Step 2:** Run `php artisan test --filter=GroupTest`. Expected: FAIL (route/class not found).
- [ ] **Step 3:** Create migrations, `Group` model + factory (factory's `configure()` attaches `created_by` user as member), `User::groups()`.
- [ ] **Step 4:** Create `GroupPolicy` (auto-discovered), the form requests (`currency`: `required|string|size:3|alpha`, uppercased in `prepareForValidation`; `email`: `required|email|exists:users,email` plus a closure rule rejecting existing members), controllers using `Gate::authorize()` and returning `Inertia::render(...)`, routes inside the `auth` group, and Vue pages using `AuthenticatedLayout`. `Groups/Show.vue` shows name, currency, member list, and an "Add member" email form (`useForm({ email: '' })`, posts to `route('groups.members.store', group.id)` via Ziggy's `route()` helper that Breeze provides).
- [ ] **Step 5:** Run `php artisan test --filter=GroupTest`. Expected: PASS.
- [ ] **Step 6:** `vendor/bin/pint`, then commit: `git commit -m "feat: groups and membership"`

---

### Task 3: Money parsing and split calculation

**Files:**
- Create: `app/Support/Money.php`
- Create: `app/Services/SplitCalculator.php`
- Create: `app/Exceptions/InvalidSplitException.php`
- Test: `tests/Unit/MoneyTest.php`, `tests/Unit/SplitCalculatorTest.php`

**Interfaces:**
- Produces:
  - `Money::toCents(string $input): ?int` — trims, strips commas, accepts `^\d+(\.\d{1,2})?$`, returns cents; returns `null` for anything else or for 0.
  - `Money::format(int $cents, string $currency): string` — e.g. `format(123456, 'PHP')` → `"PHP 1,234.56"`.
  - `SplitCalculator::split(int $amountCents, string $type, array $inputs): array<int userId, int cents>`
    - `'equal'`: `$inputs` = list of user ids.
    - `'exact'`: `$inputs` = `[userId => cents]`.
    - `'percent'`: `$inputs` = `[userId => basisPoints]` (25.5% = 2550).
    - Throws `InvalidSplitException` (with a user-facing message) on empty participants, exact sum ≠ amount, percent sum ≠ 10000, or any negative input.
    - Result keys sorted ascending by user id.

- [ ] **Step 1: Write failing tests**

```php
// MoneyTest
toCents('12.3') === 1230; toCents('12.30') === 1230; toCents(' 7 ') === 700
toCents('1,000') === 100000
toCents('12.345') === null; toCents('0') === null; toCents('-5') === null; toCents('abc') === null; toCents('') === null
format(123456, 'PHP') === 'PHP 1,234.56'; format(5, 'USD') === 'USD 0.05'

// SplitCalculatorTest
split(10000, 'equal', [3, 1, 2]) === [1 => 3334, 2 => 3333, 3 => 3333]
split(10000, 'exact', [1 => 7000, 2 => 3000]) === [1 => 7000, 2 => 3000]
split(10000, 'exact', [1 => 7000, 2 => 2000]) throws InvalidSplitException
split(10001, 'percent', [1 => 5000, 2 => 5000]) === [1 => 5001, 2 => 5000]
split(1000, 'percent', [1 => 3333, 2 => 3333, 3 => 3334]) sums to 1000
split(10000, 'percent', [1 => 6000, 2 => 3000]) throws InvalidSplitException
split(10000, 'equal', []) throws InvalidSplitException
```

- [ ] **Step 2:** Run `php artisan test --filter="MoneyTest|SplitCalculatorTest"`. Expected: FAIL.
- [ ] **Step 3: Implement.** Equal: `intdiv` base share, the first `amount % n` users (by ascending id) get +1 cent. Percent: each share = `intdiv(amount * bp, 10000)`; hand out leftover cents one at a time to users with the largest remainder `(amount * bp) % 10000`, ties to the lower user id.
- [ ] **Step 4:** Run the same filter. Expected: PASS.
- [ ] **Step 5:** `vendor/bin/pint`, commit: `git commit -m "feat: money parsing and split calculator"`

---

### Task 4: Expenses

**Files:**
- Create: migrations `xxxx_create_expenses_table.php`, `xxxx_create_expense_shares_table.php`
- Create: `app/Models/Expense.php`, `app/Models/ExpenseShare.php`, `database/factories/ExpenseFactory.php`
- Modify: `app/Models/Group.php` (add `expenses()`)
- Create: `app/Http/Requests/ExpenseRequest.php`, `app/Http/Controllers/ExpenseController.php`
- Create: `resources/js/Pages/Expenses/Create.vue`, `Edit.vue`, `resources/js/Components/ExpenseForm.vue`
- Modify: `resources/js/Pages/Groups/Show.vue` (expense list + "Add expense"), `app/Http/Controllers/GroupController.php` (`expenses` prop), `routes/web.php`
- Test: `tests/Feature/ExpenseTest.php`

**Interfaces:**
- Consumes: `Money::toCents`, `SplitCalculator::split`, `InvalidSplitException`, `GroupPolicy::update`, `Group::hasMember`.
- Produces:
  - `expenses`: `id, group_id (FK cascade), paid_by (FK users), created_by (FK users), description (string 255), amount_cents (unsignedBigInteger), split_type (string: equal|exact|percent), spent_on (date), timestamps`
  - `expense_shares`: `id, expense_id (FK cascade), user_id (FK users), amount_cents`, unique `(expense_id, user_id)`
  - `Expense::shares(): HasMany`, `Expense::payer(): BelongsTo<User>`, `Group::expenses(): HasMany`
  - Routes (nested, `scopeBindings()`): `groups.expenses.create|store|edit|update|destroy` under `/groups/{group}/expenses`
  - Form fields: `description`, `amount` (string), `spent_on`, `paid_by`, `split_type`, `participants[]` (user ids, for equal), `shares[userId]` (string money, for exact), `percents[userId]` (string like `"33.33"`, for percent)
  - Inertia props: `Expenses/Create` → `group`, `members`; `Expenses/Edit` → those plus `expense: {id, description, amount, spent_on, paid_by, split_type, participants, shares, percents}` pre-filled as the strings the form posts. `Groups/Show` gains `expenses: [{id, description, spent_on, payer_name, amount_cents, amount_display}]`, newest first.
  - `ExpenseForm.vue` props: `group`, `members`, `expense` (optional), `submitUrl`, `method` (`'post'|'put'`).

- [ ] **Step 1: Write failing tests in `tests/Feature/ExpenseTest.php`**

```php
test_member_adds_equal_expense_and_shares_are_saved()
    // amount '100', 3 participants → expense amount_cents 10000; shares 3334/3333/3333
test_exact_split_saved()
test_percent_split_saved()                  // percents '50','30','20' on '200' → 10000/6000/4000
test_exact_shares_not_matching_total_is_rejected()   // assertSessionHasErrors('shares'); Expense::count() === 0
test_percents_not_summing_to_100_is_rejected()       // assertSessionHasErrors('percents')
test_invalid_amount_is_rejected()                    // '12.345' and '0' → assertSessionHasErrors('amount')
test_payer_must_be_group_member()                    // outsider id → assertSessionHasErrors('paid_by')
test_participant_must_be_group_member()              // assertSessionHasErrors('participants.0')
test_non_member_cannot_create_expense()              // assertForbidden()
test_editing_expense_replaces_shares()
test_deleting_expense_removes_shares()
test_expense_from_other_group_returns_404()          // /groups/{mine}/expenses/{theirs}/edit → assertNotFound()
test_edit_page_prefills_expense()                    // assertInertia: component('Expenses/Edit'), where('expense.amount', '100.00'), where('expense.split_type', 'equal')
test_group_page_lists_expenses()                     // assertInertia: where('expenses.0.amount_display', 'PHP 100.00')
```

- [ ] **Step 2:** Run `php artisan test --filter=ExpenseTest`. Expected: FAIL.
- [ ] **Step 3:** Migrations, models, factory.
- [ ] **Step 4:** `ExpenseRequest`: `authorize()` → user can `update` the route group; rules use `Rule::in($group->members->pluck('id'))` for `paid_by` and each participant/share/percent key. In `after()` validation hook, parse amounts with `Money::toCents`, convert percents to basis points with `Money::toCents` (same string format, so `"33.33"` → 3333, no floats), call `SplitCalculator::split`, and turn `InvalidSplitException` into an error on `shares` or `percents` (or `participants` for equal). Expose the computed shares via `$request->computedShares(): array`.
- [ ] **Step 5:** `ExpenseController` store/update writes the expense and its shares inside `DB::transaction` (update deletes old shares then inserts new), then redirects to `groups.show` with a `success` flash. `ExpenseForm.vue`: `useForm` with all fields; a split-type radio group switches which input section shows (`v-if`); equal = member checkboxes (all checked by default), exact = an amount box per member, percent = a percent box per member. Show a live "remaining" hint for exact/percent computed from the typed values — display only; the server stays the authority. Show `form.errors` per field, including `shares`, `percents`, `participants`. `Groups/Show.vue` lists expenses with edit link and a delete button (confirm, then `router.delete`).
- [ ] **Step 6:** Run `php artisan test --filter=ExpenseTest` and `npm run build`. Expected: PASS, build succeeds.
- [ ] **Step 7:** `vendor/bin/pint`, commit: `git commit -m "feat: add, edit and delete expenses"`

---

### Task 5: Balances and debt simplification

**Files:**
- Create: `app/Services/BalanceCalculator.php`, `app/Services/DebtSimplifier.php`
- Test: `tests/Unit/DebtSimplifierTest.php`, `tests/Feature/BalanceCalculatorTest.php` (needs DB)

**Interfaces:**
- Consumes: `Group::members()`, `Group::expenses()`, `Expense::shares()`. Settlements don't exist yet; Task 6 adds them to `forGroup`.
- Produces:
  - `BalanceCalculator::forGroup(Group $group): array<int userId, int cents>` — every member present (zero if untouched), sum of all values is 0. Payer `+amount_cents`, each share user `-share`.
  - `DebtSimplifier::simplify(array<int, int> $balances): array<array{from:int, to:int, amount:int}>` — repeatedly match the largest debtor with the largest creditor (ties → lower user id), transfer `min(|debt|, credit)`.

- [ ] **Step 1: Write failing tests**

```php
// DebtSimplifierTest
simplify([1 => 0, 2 => 0]) === []
simplify([1 => 1000, 2 => -1000]) === [['from' => 2, 'to' => 1, 'amount' => 1000]]
simplify([1 => 2000, 2 => -1000, 3 => -1000]) === [['from'=>2,'to'=>1,'amount'=>1000], ['from'=>3,'to'=>1,'amount'=>1000]]
// chain A owes B 10, B owes C 10 → balances [1=>-1000, 2=>0, 3=>1000] → single transfer 1→3

// BalanceCalculatorTest
// A pays 300 split equally among A,B,C → [A=>20000, B=>-10000, C=>-10000]
// plus B pays 90 for B and C → [A=>20000, B=>-5500, C=>-14500]; array_sum === 0
// a member with no expenses appears with 0
```

- [ ] **Step 2:** Run `php artisan test --filter="DebtSimplifierTest|BalanceCalculatorTest"`. Expected: FAIL.
- [ ] **Step 3:** Implement both. Load shares with one query (`ExpenseShare` joined to the group's expenses), not N+1.
- [ ] **Step 4:** Run the filter. Expected: PASS.
- [ ] **Step 5:** `GroupController::show` adds props `balances: [{user_id, name, cents, display}]` and `suggestions: [{from_id, from_name, to_id, to_name, cents, display, amount}]` (`amount` = plain string like `"100.00"` for the Task 6 settle form). Create `resources/js/Components/BalanceList.vue` (green "is owed", red "owes", grey "settled up") and a "Suggested payments" section in `Groups/Show.vue`. Add `test_group_page_passes_balances_and_suggestions` to `GroupTest` (assertInertia: `where('balances.0.cents', 20000)`, `has('suggestions', 2)`).
- [ ] **Step 6:** `php artisan test` and `npm run build`. Expected: all PASS. `vendor/bin/pint`, commit: `git commit -m "feat: group balances and suggested payments"`

---

### Task 6: Settlements

**Files:**
- Create: migration `xxxx_create_settlements_table.php`, `app/Models/Settlement.php`
- Modify: `app/Models/Group.php` (add `settlements()`), `app/Services/BalanceCalculator.php`
- Create: `app/Http/Requests/SettlementRequest.php`, `app/Http/Controllers/SettlementController.php`
- Modify: `resources/js/Pages/Groups/Show.vue`, `app/Http/Controllers/GroupController.php` (`settlements` prop), `routes/web.php`
- Test: `tests/Feature/SettlementTest.php`, extend `tests/Feature/BalanceCalculatorTest.php`

**Interfaces:**
- Consumes: `GroupPolicy::update`, `Money::toCents`, `BalanceCalculator::forGroup`, `DebtSimplifier::simplify`.
- Produces:
  - `settlements`: `id, group_id (FK cascade), from_user_id, to_user_id, amount_cents, settled_on (date), created_by, timestamps`
  - `Group::settlements(): HasMany`
  - Routes: `groups.settlements.store` POST `/groups/{group}/settlements`, `groups.settlements.destroy` DELETE `/groups/{group}/settlements/{settlement}` (scoped)
  - Balance effect: `from` `+amount`, `to` `-amount`.

- [ ] **Step 1: Write failing tests**

```php
// BalanceCalculatorTest (add)
// A pays 300 split A,B,C; B settles 100 to A → [A=>10000, B=>0, C=>-10000]

// SettlementTest
test_member_records_settlement()                 // POST from=B,to=A,amount='100' → row exists, redirect to group
test_from_and_to_must_differ()                   // assertSessionHasErrors('to_user_id')
test_both_parties_must_be_members()              // outsider → assertSessionHasErrors
test_non_member_cannot_record_settlement()       // assertForbidden()
test_settlement_can_be_deleted()
test_group_page_lists_settlements()              // assertInertia: where('settlements.0.amount_display', 'PHP 100.00')
```

- [ ] **Step 2:** Run `php artisan test --filter="SettlementTest|BalanceCalculatorTest"`. Expected: FAIL.
- [ ] **Step 3:** Migration, model, relation, extend `BalanceCalculator::forGroup`, request (`different:from_user_id`, membership rules as in Task 4), controller.
- [ ] **Step 4:** `Groups/Show` gains `settlements: [{id, from_name, to_name, settled_on, amount_cents, amount_display}]`. In `Groups/Show.vue`, each suggested payment gets a "Mark as paid" button (`router.post` with its `from_id`, `to_id`, `amount`, today's date); add a manual "Record a payment" form (`useForm`, from/to member selects, amount, date) and a payment history list with delete buttons.
- [ ] **Step 5:** `php artisan test` and `npm run build`. Expected: all PASS. `vendor/bin/pint`, commit: `git commit -m "feat: record settlements"`

---

### Task 7: Dashboard and member removal

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `resources/js/Pages/Dashboard.vue`, `routes/web.php` (point `dashboard` at the controller; `/` redirects to `dashboard` when signed in, to `login` otherwise — replacing Breeze's `Welcome` page)
- Modify: `app/Http/Controllers/GroupMemberController.php` (add `destroy`), `resources/js/Pages/Groups/Show.vue` (remove-member button)
- Test: `tests/Feature/DashboardTest.php`, extend `tests/Feature/GroupTest.php`

**Interfaces:**
- Consumes: `User::groups()`, `BalanceCalculator::forGroup`, `Money::format`.
- Produces: route `groups.members.destroy` DELETE `/groups/{group}/members/{user}`; `Dashboard` props `groups: [{id, name, currency, my_balance_cents, my_balance_display}]`.

- [ ] **Step 1: Write failing tests**

```php
// DashboardTest
test_dashboard_lists_my_groups_with_my_balance()  // A is owed 200 in "Baguio Trip" → assertInertia: component('Dashboard'), where('groups.0.name', 'Baguio Trip'), where('groups.0.my_balance_display', 'PHP 200.00')
test_dashboard_with_no_groups_passes_empty_list()  // assertInertia: has('groups', 0)  (Dashboard.vue shows "Create your first group" when empty)

// GroupTest (add)
test_member_with_zero_balance_can_be_removed()
test_member_with_nonzero_balance_cannot_be_removed()   // assertSessionHasErrors('member'), still a member
test_last_member_cannot_be_removed()                   // group never ends up with zero members
```

- [ ] **Step 2:** Run `php artisan test --filter="DashboardTest|GroupTest"`. Expected: FAIL.
- [ ] **Step 3:** Implement the dashboard controller/view and `GroupMemberController::destroy` (authorize `update`, check balance via `BalanceCalculator`, detach).
- [ ] **Step 4:** `php artisan test` and `npm run build`. Expected: all PASS.
- [ ] **Step 5:** Manual check in the browser: `composer run dev`, sign up two users, create a group, add the second user, add one expense of each split type (watch the "remaining" hint and the error messages when exact/percent don't add up), use "Mark as paid", confirm balances hit zero and the dashboard agrees.
- [ ] **Step 6:** `vendor/bin/pint`, commit: `git commit -m "feat: dashboard and member removal"`
