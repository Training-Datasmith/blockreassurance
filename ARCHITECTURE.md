# Architecture: blockreassurance

## Purpose

A PrestaShop module (Block Reassurance) that displays configurable reassurance icons and text on storefront pages — below/above the header, on product pages, and on checkout pages. Shop owners manage reassurance items (icon, title, description, display position) via the back-office admin panel.

## Directory Structure

```
blockreassurance.php               — Module entry point: extends Module + WidgetInterface; registers hooks
src/
  Entity/
    Psreassurance.php              — Doctrine entity: reassurance item (icon, title, description, status, position)
    Psreassurance_Lang.php         — Doctrine entity: per-language translation of title and description
  Form/
    Psreassurance_Form_Data_Handler.php  — Handles create/update form submission; maps form data to entity
  Repository/
    Psreassurance_Repository.php   — Doctrine repository: finds items by position/language/status

controllers/
  admin/
    AdminBlockReassuranceController.php  — Back-office CRUD controller for managing reassurance items

views/
  templates/
    hook/                          — Smarty templates rendered in each hook position
    admin/                         — Back-office item management views

upgrade/                           — Migration scripts run on module upgrade
```

## Key Design Decisions

- **WidgetInterface** — implements `renderWidget()` and `getWidgetVariables()` so the module can be embedded as a widget in theme templates, not only via hooks.
- **Position constants** — four display positions (`POSITION_NONE`, `POSITION_BELOW_HEADER`, `POSITION_ABOVE_HEADER`, and page-specific hooks) encoded as constants; each item stores its position in the DB.
- **Doctrine entities with lang table** — uses a `*_lang` entity for multilingual support, following PrestaShop's standard translation pattern.
- **Hook-based rendering** — the module registers hooks (`displayHeader`, `displayFooter`, `displayProductAdditionalInfo`, `displayCheckoutSummaryTop`) and renders the appropriate Smarty template per hook.

## Extension Points

- Add new display positions by defining additional hook methods and position constants.
- Extend `Psreassurance_Form_Data_Handler` to add custom field validation.
- Override Smarty templates in the active theme's `modules/blockreassurance/` directory.

## Dependency Flow

```
PrestaShop hook call
  └── blockreassurance::hookDisplay*()
        └── Psreassurance_Repository::findByPosition()
              └── Doctrine ORM → ps_psreassurance table
                    └── Smarty::render(template, variables) → HTML widget
```
