# Pharmacy FYP Project Explanation Book

## 1. Project Introduction

I built this project as a web-based Pharmacy / Pharmaceutical Distribution Management System for small and medium distributors.  
The main idea of the system is to manage medicine stock, batches, purchase orders, sales invoices, party records, reports, and finance records from one backend panel.

This is not a simple shop billing project only.  
I designed it as an operational back-office system where the user can:

- manage product master and inventory
- track batches and expiry
- create and receive purchase orders
- manage customers and suppliers
- create invoices and sales returns
- view finance reports like ledger, trial balance, cash book, bank book, and GST report
- control users, roles, settings, notifications, exports, print, and PDF

## 2. Main Objective

My main objective was to reduce manual work and make the system useful for real daily operation.

In normal pharmacy distribution work, many problems come from:

- stock not matching actual quantity
- expired items being missed
- supplier bills not being tracked properly
- purchase flow not updating stock correctly
- reports not being ready when management needs them

So I built the system in a way where data flows from one module into another instead of keeping everything disconnected.

## 3. Main Modules I Built

### 3.1 Dashboard

I built the dashboard as the quick control center of the system.

From the dashboard, I can immediately see:

- total categories
- total products
- total batches
- total suppliers
- monthly purchase value
- all time purchase value
- total stock quantity
- system users
- low stock alert
- expiry alert
- top suppliers
- recent purchase orders

I also added analytics charts:

- purchase trend line chart
- stock by category doughnut chart
- overview bar chart

The dashboard is also role-aware.  
If a user does not have permission for a module, I hide that section so the screen stays clean and not confusing.

### 3.2 Inventory Management

For inventory, I created these parts:

- category
- unit
- product master
- inventory products
- batch management
- stock adjustment

Product master is for creating and maintaining the medicine record.  
Inventory products is for checking actual stock side with live quantity.

Batch management is important because in pharmacy work, batch number and expiry are not optional.  
That is why I tracked quantity at batch level.

Stock adjustment is there for cases like:

- damaged stock
- expired stock
- return adjustment
- manual add/subtract correction

### 3.3 Purchase Management

I completed the purchase side in a structured way:

- purchase order list
- create purchase order
- approve purchase order
- receive goods
- mark payment

The most important logic here is the receive process.

When I receive a purchase order:

1. purchase order item gets quantity received
2. batch record gets created
3. stock becomes available in inventory
4. purchase status becomes received
5. payment status can be tracked separately

This is important because in a real system, I should not manually update stock after receiving goods.  
The receive flow itself should update inventory.

### 3.4 Sales / POS

Even though the earlier written proposal focused more on inventory and purchase, I later expanded the system to include sales side also.

I added:

- sales invoice list
- create invoice
- invoice detail page
- print invoice
- PDF invoice
- payment update
- sales return

I supported:

- retail sale
- wholesale sale
- credit sale

I also added tax and discount support at invoice item level so the invoice module feels closer to real business use.

### 3.5 Party Management

I created party management mainly for customers and institutions.

This section includes:

- party master list
- create and edit party
- active / inactive control
- credit limit
- opening balance
- current balance
- ledger view
- aging information

This helps track which customers still owe money and which institution has credit history.

### 3.6 Accounting and Finance

I also completed finance screens to make the project stronger and closer to the bigger system design.

I built:

- general ledger
- account tree
- trial balance
- cash book
- bank book
- GST / tax report
- expense tracking

These reports are useful because management and accountant users do not only want stock data.  
They also want financial movement and summary.

I used grouped accounting entries so trial balance and account tree stay understandable even when the raw transaction count grows.

## 4. Roles and Permissions

I used Spatie Laravel Permission for roles and permissions.

I kept it because it is better than using only a simple enum field when the project becomes bigger.

The main roles are:

- admin
- staff
- procurement

With this, I can decide which user can:

- see inventory
- manage purchase
- access reports
- manage users
- open settings

This also makes the dashboard cleaner because I only show cards and buttons that match the logged-in user permission.

## 5. Reports and Export System

I added export, print, and PDF support in the system because in a real office this is always needed.

I used:

- Excel export for tables and reports
- PDF export for report-style pages
- print view for quick hard copy output

Important reports included are:

- low stock report
- expiry alert report
- purchase history report
- supplier performance report
- ledger
- trial balance
- cash book
- bank book
- GST report

I also made sure tables support filters and search where they actually help the user.

## 6. Notifications and Settings

I added a notification tray in the header.  
It supports:

- unread highlight
- mark all read
- load more

For settings, I created backend-manageable values like:

- app name
- logo
- favicon
- company details
- mail settings
- currency symbol
- tax rate

That way I do not need to hardcode every business setting inside the code.

## 7. How Data Flows in My System

This is the most important part to understand the project clearly.

### Purchase to Inventory Flow

When I create a purchase order, it starts as pending.  
After approval, I receive the goods.  
During receive:

- line items are completed
- batch details are saved
- stock is added
- purchase status becomes received

So inventory is connected directly to purchase.

### Sales to Finance Flow

When I make a sales invoice:

- invoice items are saved
- quantity is reduced from the selected batch
- payment and due amount are tracked
- accounting entries are recorded

If there is a sales return:

- stock can be adjusted back
- finance side records refund effect

### Expense to Finance Flow

When I save an expense:

- expense record is created
- accounting transaction is posted
- cash or bank side is affected depending on payment mode

That is why ledger, cash book, bank book, and trial balance all stay connected.

## 8. Why I Used Batch Tracking

In pharmacy distribution, batch-level control is necessary because:

- different purchase dates can have different expiry dates
- same product can come from different suppliers
- recall and traceability becomes possible only when batch is tracked
- expiry alert becomes meaningful only at batch level

So I did not design the system as simple product quantity only.  
I kept it batch-based where it matters.

## 9. Why I Added Old Seeder History

I changed the demo seeder so it does not only show fresh recent records.

I kept some older records up to multiple years back because:

- finance reports should not look empty
- trend charts should feel believable
- account pages should have historical movement
- viva demo becomes stronger when the system looks used over time

At the same time, I kept old stock from affecting current dashboard badly by storing older expired or zero-available batch history in a controlled way.

## 10. Technical Stack I Used

I used:

- Laravel 10
- MySQL
- Blade templates
- Bootstrap 5
- jQuery
- DataTables
- Chart.js
- Spatie Laravel Permission
- DomPDF
- FastExcel

This stack is practical for a college FYP because it is strong enough for a real admin panel but still understandable to explain during viva.

## 11. Important Design Decisions

Some decisions I made intentionally are:

### Role-aware UI
I hide sections the user cannot use.

### Server-side tables where useful
For heavier lists like users and party records, I use server-side table loading.

### Simpler search for grouped finance reports
For account tree and trial balance, I did not force DataTables because grouped subtotal rows are easier to break.  
Instead, I used cleaner report tables with direct search.

### Shared helpers and shared UI logic
I reused common helpers for:

- currency formatting
- notification display
- print logic
- DataTable setup

This keeps the code cleaner and easier to maintain.

## 12. How to Demonstrate the Project

If I need to show the project to teacher, supervisor, or external reviewer, I can explain in this order:

1. login and role-based access
2. dashboard overview and alerts
3. product and batch management
4. purchase order creation and receiving flow
5. sales invoice and payment handling
6. party management and ledger
7. finance reports
8. settings, notifications, export, print, and PDF

That order tells a full story from operation to reporting.

## 13. Final Summary

In simple words, I built a backend system that connects:

- stock
- batches
- purchase
- sales
- parties
- finance
- reports
- users
- settings

The main strength of my project is that it is not only CRUD screens.  
The modules are connected, the reports are usable, and the system can be presented as a practical pharmacy distribution management solution.
