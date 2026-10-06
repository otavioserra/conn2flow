---
title: "Site administration with Tailwind"
description: "Products, coupons, subscriptions, shipping, presentations and site panel operations."
section: guides
order: 96
verified_at: 914c7b10
---

# Site administration with Tailwind

Conn2Flow Site modules use the Core's Tailwind v4 panel: fields, selects, tabs, cards, tables and tooltips share behavior. Open modules from the sidebar or Dashboard. Availability depends on profile permissions; a guide does not grant access to actions.

## Products and catalog

In Products, edit descriptions with Quill, choose images through the file picker and review price, promotion, dates and shipping. Monetary fields display a mask and submit decimals. The media button opens the administrative picker; review the selected file before saving. Product pages provide visual editing and a public link. Disable/delete actions have their own tooltips; deletion requests confirmation.

Stripe Products places title and actions before the synchronization box. Stripe-linked products inherit protected source data; locked fields cannot change the source price. Products Index provides compact template cards, widget preview and a copy button. Product Types uses the panel's shared fields, selectors and checkboxes. Product Reviews retains standardized selects and buttons.

## Coupons and affiliates

Select the discount type before entering a value. Coupons alternates two fields: percentage from 0 to 100 with up to two decimals, and a fixed amount with the selected currency mask. Only the active field submits discount_value; the other is hidden and disabled. Minimum amount also uses a currency mask. Where It Applies offers multiple product/subscription selections; duration controls recurrence-specific fields.

Affiliates uses monetary/percentage fields and shared controls. Masks help entry; limits and authorization are still checked by the server. A formatted value does not confirm a payment or payout.

## Shipping, orders and reports

Shipping Methods combines its list and method form: name, type and JSON configuration. Available screen types are fixed_table, free_over, pickup and melhor_envio. Review the method's required JSON before saving. Edit/delete buttons have tooltips; deletion uses a form and confirmation.

Orders uses shared selects in the buyer form. Sales Reports uses standardized type selection and filters; review them before producing a report. Analytics Manager uses the same searchable select on its main and pipeline pages. Changing the controls does not change calculation or external integration rules.

## Subscriptions and gateways

Subscriptions retains floating selectors, edit actions with tooltips and a logo modal. Plans and service stages use standardized fields and compact copy buttons. Gateways highlights the default choice; configuring a provider still requires integration details. The visual migration does not validate real charges: use the provider's test environment for those operations.

## Presentations and Dashboard

Create or edit a presentation, use Visual Edit and review slides. To display it on the Dashboard, open Widgets and Metrics, choose presentations and an active record in the current language. Arrows, dots, counter and progress depend on presentation options and having more than one slide. Fullscreen depends on its corresponding option.

The public widget loads authored template CSS and head, followed by record styles. The template's partial precompiled sheet is not appended after the page sheet, preserving responsive slide layout. In the Dashboard, the iframe's Tailwind compiler generates the complete sheet last. Embedded presentations fill the card's usable height. See [Dashboard](../reference/modules/dashboard.md).

## Operations, content and documentation

Host Manager retains common navigation across its seven administrative screens. Its action menu closes when clicking outside; titles and icons follow the panel. Social Connections, Social Apps, 3D catalog and distributed modules use Tailwind with their own permissions and operations. The editor's Social Workspace depends on the project modules that provide it.

Documentation displays the last build's state. Private guides at documentation/<module>/ enumerate declared administrative actions, excluding edit, clone and view pages. Public docs/ content is generated from bilingual Markdown; editing generated resources does not persistently update documentation.

## Verified sources

Checked against controllers, scripts, widgets, manifests and authored pages for products, stripe-products, products-index, product-types, product-reviews, coupons, affiliates, shipping-methods, orders, sales-reports, analytics-manager, subscriptions, subscriptions-plans, subscriptions-service-stages, gateways-pagamentos, presentations, host-manager and documentation in Conn2Flow Site consolidation a7192e75. Shared behavior is documented in [Administrative interface](../concepts/admin-interface.md) and [Controls](../reference/libraries/controles.md).
