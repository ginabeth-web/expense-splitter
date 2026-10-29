# Expense Splitter

A web app for splitting shared costs with friends, housemates or travel buddies. Log who paid for what, split it the way you agreed, and the app tells you who owes whom — then lets you record the paybacks until everyone is settled up.

Built with **Laravel 12**, **Vue 3**, **Inertia.js** and **Vuetify 3**.

---

## Table of contents

- [Features](#features)
- [How it works](#how-it-works)
- [Tech stack](#tech-stack)
- [Getting started](#getting-started)
- [Running the app](#running-the-app)
- [Running the tests](#running-the-tests)
- [Project structure](#project-structure)
- [Data model](#data-model)
- [Routes](#routes)
- [Business rules](#business-rules)
- [Money handling](#money-handling)
- [Laravel Boost (AI tooling)](#laravel-boost-ai-tooling)
- [Troubleshooting](#troubleshooting)
- [Known limitations](#known-limitations)

---

## Features

- **Accounts** — register, log in, reset your password and manage your profile.
- **Groups** — create a group for a trip, household or event, with its own currency (default `PHP`).
- **Members** — add people to a group by the email they signed up with.
- **Expenses** — record what was bought, how much, when, and who paid. Edit or delete any expense later.
- **Three ways to split**
  - **Equally** — tick who shares it; the app divides it evenly.
  - **Exact amounts** — type each person's share.
  - **Percentage** — type each person's percentage.
  - A live "left to assign" hint shows when amounts or percentages don't add up yet.
- **Balances** — see at a glance who is owed money and who owes.
- **Suggested payments** — the app works out a short list of paybacks that settles everyone, with a one-click **Mark as paid** button.
- **Payments** — record any payback manually (full or partial), and delete one if it was entered by mistake.
- **Dashboard** — every group you're in, with your own balance in each.
- **Responsive layout** — side navigation on desktop, a slide-out menu on phones.

---

## How it works

### The everyday flow

1. **Create a group**, e.g. "Baguio Trip".
2. **Add members** by email. They need to have registered first.
3. **Add expenses** as they happen: "Hotel, ₱300, paid by Ana, split equally".
4. **Check the balances.** Positive means the group owes you; negative means you owe.
5. **Pay each other back** outside the app (cash, GCash, bank transfer), then record it with **Mark as paid** or the **Payments** form.
6. When every balance shows **settled up**, you're done.

### Expenses vs. payments

These two are easy to mix up:

| | **Expense** — "Paid by" | **Payment** — "Who paid" → "Paid to" |
|---|---|---|
| Meaning | Someone covered a shared cost for the group | Someone paid back a person they owed |
| Effect | The others now owe the payer their share | The payer's debt goes down, and so does what the receiver is owed |
| Example | Ana pays ₱300 for the hotel, split 3 ways | Ben gives Ana ₱100 back |

### A worked example

Ana, Ben and Carla are in a group.

1. Ana pays **₱300** for the hotel, split equally → each share is ₱100.
   Ana paid ₱300 and her share is ₱100, so she is **owed ₱200**. Ben and Carla each **owe ₱100**.
2. Ben pays **₱90** for dinner, split between Ben and Carla → ₱45 each.
   Ben's balance moves from −₱100 to −₱55. Carla's moves from −₱100 to −₱145.
3. Balances now: **Ana +₱200, Ben −₱55, Carla −₱145** (they always add up to zero).
4. Suggested payments: **Carla pays Ana ₱145**, **Ben pays Ana ₱55**.
5. After both are marked as paid, everyone is **settled up**.

### How balances are calculated

For each member of a group:

```
balance = (total they paid for expenses)
        − (total of their shares in expenses)
        + (payments they made to others)
        − (payments they received from others)
```

A positive balance means they are owed money; negative means they owe. The balances of a group always sum to exactly zero.

### How suggested payments are chosen

The app repeatedly pairs the person who **owes the most** with the person who is **owed the most**, and has the first pay the second as much as possible (whichever amount is smaller). It repeats until everyone is at zero. This keeps the number of paybacks small — for example, "A owes B ₱10 and B owes C ₱10" becomes a single "A pays C ₱10". Ties are broken by user ID so the result is always the same.

### How splits handle odd cents

- **Equal split:** ₱100.00 between 3 people becomes **₱33.34 / ₱33.33 / ₱33.33**. Leftover cents go one at a time to members in user-ID order.
- **Percentage split:** each share is rounded down, then the leftover cents go to whoever lost the most in rounding (ties by user ID).
- **Every split adds up to exactly the expense amount** — no cent is ever lost or invented.

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, [Laravel 12](https://laravel.com/docs/12.x) |
| Frontend | [Vue 3](https://vuejs.org/) with [Vuetify 3](https://vuetifyjs.com/) and [Material Design Icons](https://pictogrammers.com/library/mdi/) |
| Glue | [Inertia.js 2](https://inertiajs.com/) (server-side routing, client-side pages — no separate API) and [Ziggy](https://github.com/tighten/ziggy) (Laravel route names in JavaScript) |
| Auth scaffolding | [Laravel Breeze](https://laravel.com/docs/12.x/starter-kits) (Vue stack), restyled with Vuetify |
| Build | [Vite 7](https://vitejs.dev/) with `vite-plugin-vuetify` |
| Database | SQLite by default (any database Laravel supports will work) |
| Tests | PHPUnit 11 |
| Code style | [Laravel Pint](https://laravel.com/docs/12.x/pint) |
| AI tooling | [Laravel Boost](https://laravel.com/docs/12.x/boost) |

---

## Getting started

### Requirements

- **PHP 8.2 or newer** with the `pdo_sqlite`, `mbstring`, `openssl` and `fileinfo` extensions (XAMPP works)
- **Composer 2**
- **Node.js 20 or newer** and npm

### Installation

```bash
git clone <your-repo-url> expense-splitter
cd expense-splitter
```

Install the PHP and JavaScript dependencies:

```bash
composer install
npm install
```

Create your environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the database and load the two test users:

```bash
php artisan migrate:fresh --seed
```

> If `database/database.sqlite` doesn't exist yet, Laravel offers to create it during `migrate`. Answer **yes**.

Build the frontend once:

```bash
npm run build
```

> Shortcut: `composer run setup` runs install, key generation, migrations and the build in one go (without seeding).

---

## Running the app

Start everything (Laravel server, queue worker, log viewer and Vite with hot reload) with one command:

```bash
composer run dev
```

Then open **http://localhost:8000**.

If you'd rather run the pieces yourself, use two terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

### Test accounts

`php artisan migrate:fresh --seed` creates two users so you can try a group with two people:

| Name | Email | Password |
|---|---|---|
| Test User | `test@example.com` | `password` |
| Friend User | `friend@example.com` | `password` |

You can also register your own accounts from the sign-up page.

> ⚠️ `migrate:fresh` **wipes all data**. Use plain `php artisan migrate` to apply new migrations without losing anything.

---

## Running the tests

```bash
php artisan test
```

Run one file or one test:

```bash
php artisan test --filter=SplitCalculatorTest
```

Format the code before committing:

```bash
vendor/bin/pint
```

### What the tests cover

| Test file | What it checks |
|---|---|
| `tests/Unit/MoneyTest.php` | Parsing typed amounts into cents (including bad input such as `12.345`, `12,50`, `-5`, huge numbers) and formatting cents for display |
| `tests/Unit/SplitCalculatorTest.php` | Equal, exact and percentage splits, leftover cents, and rejecting splits that don't add up |
| `tests/Unit/DebtSimplifierTest.php` | Turning balances into suggested payments |
| `tests/Feature/BalanceCalculatorTest.php` | Balances from expenses and payments, per group |
| `tests/Feature/GroupTest.php` | Creating groups, adding and removing members, page data, access control |
| `tests/Feature/ExpenseTest.php` | Adding, editing and deleting expenses, validation, access control |
| `tests/Feature/SettlementTest.php` | Recording and deleting payments, validation, access control |
| `tests/Feature/DashboardTest.php` | Dashboard balances and home-page redirects |
| `tests/Feature/ProfileTest.php`, `tests/Feature/Auth/*` | Breeze's account features, plus the account-deletion guard |

Page tests check the data each Vue page receives (Inertia props), so they don't need a browser or a frontend build.

---

## Project structure

Only the files written for this app are listed; the rest is standard Laravel.

```
app/
├── Exceptions/InvalidSplitException.php   Thrown when a split doesn't add up
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php        Your groups + your balance in each
│   │   ├── GroupController.php            List, create and show groups (balances, suggestions, history)
│   │   ├── GroupMemberController.php      Add and remove members
│   │   ├── ExpenseController.php          Create, edit, update, delete expenses
│   │   └── SettlementController.php       Record and delete payments
│   ├── Middleware/HandleInertiaRequests.php  Shares the signed-in user and flash messages with Vue
│   └── Requests/                          Validation for groups, members, expenses, payments
├── Models/                                Group, Expense, ExpenseShare, Settlement, User
├── Policies/GroupPolicy.php               Only group members can see or change a group
├── Services/
│   ├── SplitCalculator.php                Amount + split type → each person's share in cents
│   ├── BalanceCalculator.php              Each member's net balance in a group
│   └── DebtSimplifier.php                 Balances → suggested payments
└── Support/Money.php                      Parse "1,234.50" → 123450 cents; format cents for display

resources/js/
├── app.js                                 Boots Vue, Inertia, Ziggy and Vuetify
├── plugins/vuetify.js                     Vuetify theme (teal primary) and component defaults
├── Layouts/
│   ├── AuthenticatedLayout.vue            App bar, navigation drawer, flash snackbar
│   └── GuestLayout.vue                    Centered card for login/register pages
├── Components/
│   ├── ExpenseForm.vue                    Shared add/edit expense form with the three split types
│   ├── BalanceList.vue                    "is owed / owes / settled up" list
│   └── ConfirmDialog.vue                  Reusable "are you sure?" dialog
└── Pages/
    ├── Dashboard.vue
    ├── Groups/{Index,Create,Show}.vue
    ├── Expenses/{Create,Edit}.vue
    ├── Auth/*.vue                         Login, register, password reset, email verification
    └── Profile/*.vue                      Profile, password, delete account

database/
├── migrations/2026_10_29_*                groups, group_user, expenses, expense_shares, settlements
├── factories/                             Group and Expense factories for tests
└── seeders/DatabaseSeeder.php             The two test users

docs/superpowers/plans/                    The original implementation plan
```

---

## Data model

```
users ──< group_user >── groups
                           │
             ┌─────────────┴─────────────┐
             │                           │
          expenses                   settlements
             │                   (from_user → to_user)
       expense_shares
     (one row per person)
```

| Table | Key columns | Notes |
|---|---|---|
| `groups` | `name`, `currency` (3 letters), `created_by` | |
| `group_user` | `group_id`, `user_id` | Who is a member. Unique per pair. |
| `expenses` | `group_id`, `paid_by`, `created_by`, `description`, `amount_cents`, `split_type`, `spent_on` | `split_type` is `equal`, `exact` or `percent` |
| `expense_shares` | `expense_id`, `user_id`, `amount_cents`, `basis_points` | Each person's share. `basis_points` (2550 = 25.50%) is only set for percentage splits, so the edit form can show the percentages again. |
| `settlements` | `group_id`, `from_user_id`, `to_user_id`, `amount_cents`, `settled_on`, `created_by` | A payback from one member to another |

Deleting a group cascades to its expenses, shares and settlements.

---

## Routes

All routes below require you to be logged in. `/` sends you to the dashboard (or the login page if you're signed out).

| Method | URL | Name | What it does |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | Your groups and balances |
| GET | `/groups` | `groups.index` | List your groups |
| GET | `/groups/create` | `groups.create` | New group form |
| POST | `/groups` | `groups.store` | Create a group |
| GET | `/groups/{group}` | `groups.show` | Group page |
| POST | `/groups/{group}/members` | `groups.members.store` | Add a member by email |
| DELETE | `/groups/{group}/members/{user}` | `groups.members.destroy` | Remove a member |
| GET | `/groups/{group}/expenses/create` | `groups.expenses.create` | New expense form |
| POST | `/groups/{group}/expenses` | `groups.expenses.store` | Save an expense |
| GET | `/groups/{group}/expenses/{expense}/edit` | `groups.expenses.edit` | Edit expense form |
| PUT | `/groups/{group}/expenses/{expense}` | `groups.expenses.update` | Update an expense |
| DELETE | `/groups/{group}/expenses/{expense}` | `groups.expenses.destroy` | Delete an expense |
| POST | `/groups/{group}/settlements` | `groups.settlements.store` | Record a payment |
| DELETE | `/groups/{group}/settlements/{settlement}` | `groups.settlements.destroy` | Delete a payment |

Expense and payment URLs are **scoped** to their group: using an expense ID from another group returns 404.

Breeze also provides `/login`, `/register`, `/forgot-password`, `/profile` and related routes (see `routes/auth.php`).

---

## Business rules

**Access**
- Only members of a group can see it or change anything in it. Everyone else gets **403 Forbidden**.
- Any member can add, edit or delete any expense or payment in the group.

**Members**
- You can only add people who have already registered, by their exact email.
- Someone already in the group can't be added twice.
- A member can be removed only if their balance is exactly zero **and** they don't appear in any expense or payment in that group. This stops old records from pointing at someone who is no longer there.
- A group always keeps at least one member.

**Expenses**
- The payer and everyone sharing must be members of the group.
- Exact amounts must add up to the total; percentages must add up to 100. Otherwise the form shows an error and nothing is saved.
- In exact and percentage splits, leaving someone blank means they aren't part of the expense.

**Payments**
- "Who paid" and "Paid to" must be two different members.
- Paying more than someone owes is allowed (e.g. paying ahead); the balances simply swing the other way.

**Accounts**
- You can't delete your account while you're in a group or have created one, because your expenses and payments would lose their owner.

---

## Money handling

- **All money is stored and calculated as whole cents** (integers), never as decimals, so rounding errors can't creep in.
- Typed amounts are parsed by `App\Support\Money::toCents()`:

| You type | Stored as |
|---|---|
| `12.3` | 1230 cents |
| `1,000` | 100000 cents |
| `999,999,999,999.99` | the largest amount allowed |
| `12.345`, `0`, `-5`, `abc` | rejected |
| `12,50` | rejected (a comma is only accepted as a thousands separator) |

- Percentages use the same parser, so `33.33` becomes 3333 basis points.
- The server formats every amount for display (e.g. `PHP 1,234.56`) and sends it to Vue ready to show. The Vue pages never do money math, except the "left to assign" hint in the expense form, which is display-only — the server always makes the final check.
- Each group has one currency. There is no currency conversion.

---

## Laravel Boost (AI tooling)

This project is set up with [Laravel Boost](https://laravel.com/docs/12.x/boost) for AI-assisted development with Claude Code:

- **`.mcp.json`** starts the Boost MCP server (`php artisan boost:mcp`), which lets the AI agent read the database schema, routes, logs and Laravel docs.
- **`AGENTS.md`** holds Boost's coding guidelines; **`CLAUDE.md`** imports it so Claude Code loads it automatically.
- **`.claude/skills/`** contains Boost's agent skills (Laravel best practices, testing, Inertia + Vue and more).

Refresh the guidelines and skills when Boost releases new ones:

```bash
php artisan boost:update
```

---

## Troubleshooting

**"PHP Warning: Unable to load dynamic library 'pdo_firebird'…" on every command**
These come from XAMPP's `php.ini`, which lists extensions that aren't installed. They're harmless. To silence them, comment out the `extension=pdo_firebird` and `extension=pdo_oci` lines in `C:\xampp\php\php.ini`.

**The page looks unstyled or out of date**
Run `npm run build` (or keep `composer run dev` running) and refresh the browser.

**"Unable to locate file in Vite manifest"**
The frontend hasn't been built since a page was added. Run `npm run build` or `npm run dev`.

**Port 8000 is already in use**
Another server is running. Stop it, or start on another port with `php artisan serve --port=8001`.

**"No one has signed up with that email yet" when adding a member**
That person needs to register first, and the email must match exactly (including capital letters).

**Can't remove a member**
They still have a balance, or they appear in an expense or payment in that group. Settle up first; members with history stay in the group.

**Can't delete my account**
You're still in a group or created one. Account deletion is blocked to keep group records intact.

---

## Known limitations

- Adding a member by email is case-sensitive on SQLite.
- Typing `0` in an exact or percentage row is rejected; leave the row blank instead.
- No currency conversion — each group has a single currency.
- No receipts or attachments, notifications, recurring expenses or invitations by email.
- The dashboard calculates each group's balance separately (a few queries per group), which is fine for normal use but not optimised for hundreds of groups.

---

## License

Released under the [MIT license](https://opensource.org/licenses/MIT).
