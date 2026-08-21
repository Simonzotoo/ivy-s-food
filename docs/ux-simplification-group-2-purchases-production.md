# Workflow Simplification - Group 2: Purchases and Production

Date: 21 August 2026

## Scope audited

Suppliers, Raw and packaging materials, New purchase, Receive purchase, Purchase history, Recipes, Plan production, Record production, Complete production batch, Wastage and rejected output.

## Usability problems found

1. **Purchase and recipe forms always rendered 5-8 blank item rows** with plain `<select>` dropdowns for material/product - the same pattern already found and fixed in Group 1's POS, present here too.
2. **No live total while building a purchase or recipe** - quantity x unit cost per line was never shown until after saving.
3. **No confirmation before receiving a purchase, returning a material batch, completing a production batch, reversing one, or approving a recipe** - each is a one-click action that creates stock/cost records or locks a recipe for use, with no "are you sure."
4. **Purchases list had no search or filters**, and used plain text instead of status badges (draft/received). Same gap on materials, suppliers and recipes lists.
5. **Selecting a material required knowing its exact unit** - the unit dropdown was never pre-filled from the material's own base unit, so staff had to remember and re-select it on every line.

## What changed

- **Purchase creation** (`purchases/create.blade.php`, `PurchaseController::create/index`): dynamic add/remove item rows (starts with one), searchable material picker that auto-fills the unit from the material's base unit, a live per-line and running total, and attachment references moved under "More details" with clearer helper text (they were unlabeled path/name fields).
- **Recipe creation** (`recipes/create.blade.php`, `RecipeController::create`): same dynamic-row and searchable-material treatment as purchases.
- **Purchase detail page**: "Receive purchase" and "Return" (per batch) now go through the shared in-app confirmation modal, explaining what will happen (stock batches created / stock reduced) before it happens.
- **Production batch detail page**: "Complete production" and "Reverse production" now use the same confirmation modal, with plain-language explanations of what gets created or restored.
- **Recipe detail page**: "Approve" now confirms first, since approval unlocks the recipe for real production.
- **Record Production form**: added a short explanatory line and a note when no approved recipes exist yet (previously just an empty-looking dropdown with no explanation).
- **Lists**: purchases gained search + status filter; purchases/materials/suppliers/recipes/production batches all use the shared `.badge` status-badge styling consistently; materials list now flags items at or below their reorder level directly in the stock column.

## Tests

- Focused: `Module3MaterialsPurchasesTest` (6 tests) and `Module4RecipesProductionTest` (7 tests) - all passing, unchanged assertions (server-side behavior untouched; only the surrounding presentation changed).
- Full regression suite: 108 tests, 542 assertions, passed.

## Verification

- Pint style check passed. Frontend build passed.
- Live browser walkthrough: created a purchase end-to-end (searched a material, quantity/cost auto-computed a line and running total, unit auto-filled from the material's base unit, saved, redirected to the purchase detail page); opened the "Receive purchase" confirmation modal and confirmed its wording; exercised the recipe-create dynamic rows.
- No console/page errors during any of the above.

## Not yet done

- Suppliers and materials create/edit forms were left as-is (already short, 5-7 fields) other than adding status badges to their list views - they didn't meet the "long/complicated form" bar that would justify a "More details" split.
- Production plans list/create form not deeply reworked this pass beyond what Group 2's shared patterns already cover indirectly (recipe/product selects); revisit if it proves confusing in practice.
