# Supplier Management & Purchase Orders UI Design Specification

## Project: Rice Inventory & Financial Management System
**Current Date**: March 20, 2026  
**Target Users**: Business Owner (role-based access control)  
**Database**: MySQL with suppliers, purchase_orders, purchase_order_items, supplier_product_ratings tables

---

## ROLE-BASED ACCESS CONTROL

```
Page/Feature              | Owner | Admin | Cashier
--------------------------|-------|-------|--------
Supplier Management       | ✅    | ❌    | ❌
Purchase Orders           | ✅    | ❌    | ❌
Supplier Ratings          | ✅    | ❌    | ❌
View Payables             | ✅    | ✅    | ❌
```

**Implementation**: Session check on all supplier/PO pages
```
if($_SESSION['role'] !== 'owner') {
  header("Location: unauthorized.php");
  exit;
}
```

---

## PAGE 1: SUPPLIER LIST PAGE
**URL**: `/capstone/owner/suppliers.php`  
**Purpose**: Overview of all suppliers with quick actions  
**Access**: Owner only

### Layout Structure:
```
┌─────────────────────────────────────────┐
│ NAVBAR (Logo, User Info, Logout)        │
├──────────────────┬──────────────────────┤
│  SIDEBAR         │  MAIN CONTENT        │
│  Finance ▼       │                      │
│  • Suppliers ✓   │  Suppliers List      │
│  • Purchase POs  │  [Add New Supplier]  │
│  • Payables      │                      │
│                  │  ┌─────────────────┐ │
│                  │  │ Supplier Table  │ │
│                  │  └─────────────────┘ │
└──────────────────┴──────────────────────┘
```

### Table Columns:
| Column | Type | Purpose |
|--------|------|---------|
| Supplier ID | Text Badge | Quick reference |
| Supplier Name | Text Link | Clickable to detail page |
| Contact Person | Text | Primary contact |
| Phone Number | Text | Communication |
| Email | Text | Email link |
| Address | Text | Shipping/delivery |
| Status | Badge | Active/Inactive (green/gray) |
| Rating | Stars ⭐ | Average product rating (1-5) |
| Actions | Buttons | View / Edit / Deactivate |

### Key Features:

#### Search & Filter Bar:
- **Search Input**: Filter by supplier name, contact person, phone
- **Status Filter**: All / Active Only / Inactive Only (dropdown)
- **Sort Options**: By name (A-Z), by rating (high-low), by date added

#### Action Buttons (Per Row):
1. **View Button** → Opens Supplier Detail Modal/Page
2. **Edit Button** → Opens Edit Form (name, contact, phone, email, address)
3. **Deactivate/Activate** → Toggle supplier status (with confirmation)
4. **Delete Button** (if no active POs) → Remove supplier permanently

#### Top Page Actions:
- **"+ Add New Supplier" Button** (Primary color, top-right)
  - Opens modal or redirects to form page
  - Form fields: Name*, Contact*, Phone*, Email*, Address
  - Submit & Cancel buttons

#### Metrics Panel (Optional):
```
Total Suppliers: 15 | Active: 12 | Inactive: 3 | Avg Rating: 4.2★
```

---

## PAGE 2: SUPPLIER DETAIL PAGE
**URL**: `/capstone/owner/suppliers.php?id=X` or `/capstone/owner/supplier_detail.php?id=X`  
**Purpose**: Detailed view of single supplier with related data  
**Access**: Owner only

### Layout Structure:
```
┌──────────────────────────────────────────────────┐
│ NAVBAR                                           │
├─────────────────┬────────────────────────────────┤
│ SIDEBAR         │ MAIN CONTENT                   │
│ Finance ▼       │                                │
│ • Suppliers ✓   │ [← Back to List]               │
│ • Purchase POs  │                                │
│ • Payables      │ ╔═ SUPPLIER PROFILE ═╗        │
│                 │ ║ Name | Contact     ║        │
│                 │ ║ Phone | Email      ║        │
│                 │ ║ Address | Status   ║        │
│                 │ ║ [Edit] [Deactivate]║        │
│                 │ ╚════════════════════╝        │
│                 │                                │
│                 │ ╔═ PURCHASE ORDERS ═╗         │
│                 │ ║ Order Table        ║        │
│                 │ ║ [Create New PO]    ║        │
│                 │ ╚════════════════════╝        │
│                 │                                │
│                 │ ╔═ PRODUCT RATINGS ═╗         │
│                 │ ║ Quality/Delivery/  ║        │
│                 │ ║ Price Ratings +    ║        │
│                 │ ║ Comments           ║        │
│                 │ ╚════════════════════╝        │
│                 │                                │
│                 │ ╔═ PAYABLES SUMMARY ═╗        │
│                 │ ║ Total Outstanding  ║        │
│                 │ ║ Paid This Month    ║        │
│                 │ ║ View All Payables  ║        │
│                 │ ╚════════════════════╝        │
└─────────────────┴────────────────────────────────┘
```

### Section 1: SUPPLIER PROFILE CARD
**Purpose**: At-a-glance supplier information

#### Display Fields:
```
┌─────────────────────────────┐
│ SUPPLIER: ABC Rice Supplier │
├─────────────────────────────┤
│ Contact Person: Juan Dela   │
│ Phone: +63 917 123 4567     │
│ Email: info@abcrice.com     │
│ Address: 123 Main St, City  │
│ Status: ✅ Active           │
│ Member Since: Jan 15, 2024  │
│ Total Orders: 12            │
│ [Edit Profile] [Deactivate] │
└─────────────────────────────┘
```

**Edit Mode**: Clicking [Edit] replaces display with form fields
- Fields become editable
- Shows [Save] and [Cancel] buttons
- Same fields as "Add New Supplier"

### Section 2: PURCHASE ORDERS
**Purpose**: View and manage POs for this specific supplier

#### Mini Table (Latest 5 POs):
| PO# | Order Date | Status | Total Amount | Items | Actions |
|-----|-----------|--------|--------------|-------|---------|
| #1001 | Jan 15 | ✅ Received | ₱50,000 | 3 | View / Delete |
| #1002 | Feb 03 | 📦 Ordered | ₱75,000 | 2 | View / Mark Received |
| #1003 | Mar 10 | ⏳ Pending | ₱30,000 | 5 | View / Create / Cancel |

**Columns Explained**:
- **PO#**: Purchase order ID (clickable to PO detail view)
- **Order Date**: Date order was created
- **Status**: Badge showing (Pending=Yellow, Ordered=Blue, Received=Green, Cancelled=Red)
- **Total Amount**: Sum of all items (₱ currency)
- **Items Count**: Number of products in this PO
- **Actions**: View / Mark Received / Delete Options

**Action Buttons**:
1. **"View" Button** → Opens PO detail modal with item list, add/remove items
2. **"Mark Received"** (if status ≠ Received) → Updates status, sets received_date, shows confirmation
3. **"Delete"** (if status = Pending) → Removes PO with confirmation
4. **"Create New PO"** Button (Primary, top of section) → Opens PO creation form

**View Full List Button**: "View All POs from this Supplier" → Links to filtered PO page

---

## PAGE 3: CREATE/EDIT PURCHASE ORDER FORM
**Location**: Modal or dedicated page `/capstone/owner/create_purchase_order.php`  
**Purpose**: Add new PO or edit pending PO  
**Access**: Owner only

### Form Structure:

#### Step 1: Basic PO Information
```
┌─────────────────────────────────┐
│ Create Purchase Order           │
├─────────────────────────────────┤
│ Supplier: [ABC Rice Supplier]   │ (Pre-filled if from supplier page)
│ Order Date: [Today's Date]      │ (Date picker)
│ Expected Delivery: [+7 days]    │ (Date picker, optional)
│ Notes: [Text area]              │ (Optional, visible to all)
│                                 │
│ [Continue to Items] [Cancel]    │
└─────────────────────────────────┘
```

**Field Validations**:
- Supplier: Required, must be active
- Order Date: Cannot be in past
- Expected Delivery: Must be ≥ Order Date if provided

---

#### Step 2: Add Products to Order
```
┌───────────────────────────────────┐
│ Add Items to PO #1005             │
├───────────────────────────────────┤
│                                   │
│ Product Selection:                │
│ Product: [Dropdown ▼]             │
│   └─ Shows: Variety (Grade)       │
│   └─ Example: Premium Rice (A1)  │
│                                   │
│ Quantity: [Input field]           │
│ Unit Price: ₱[Input field]        │
│ Subtotal: ₱0.00 (auto-calc)       │
│                                   │
│ [+ Add Item] [Cancel]             │
│                                   │
├───────────────────────────────────┤
│ Items in This Order:              │
│                                   │
│ ┌──────────────────────────────┐  │
│ │Product|Qty|Price|Subtotal|❌│  │
│ ├──────────────────────────────┤  │
│ │Premium (A1)|100|₱20|₱2,000 │❌│  │
│ │Regular (B)|50|₱15|₱750     │❌│  │
│ └──────────────────────────────┘  │
│ Order Total: ₱2,750               │
│                                   │
│ [Create PO] [Cancel]              │
└───────────────────────────────────┘
```

**Features**:
- **Product Dropdown**: Shows all active products with variety & grade
- **Quantity**: Numeric input with spinner
- **Unit Price**: Shows suggested price, user can override
- **Subtotal**: Auto-calculates as Qty × Price
- **Delete Item**: Red ❌ button removes row (no confirm needed for pending items)
- **Order Total**: Displays sum of all subtotals
- **[Create PO]**: Saves PO to database with status="pending", clears form, shows success message
- **[Cancel]**: Closes modal/returns without saving

**Validation**:
- At least 1 item required
- All quantities > 0
- All prices ≥ 0
- Supplier required

---

## PAGE 4: PURCHASE ORDER DETAIL VIEW
**Location**: Modal or detail page within supplier page or dedicated view  
**Purpose**: View/edit items in existing PO, mark as received  
**Access**: Owner only

### Layout:
```
┌────────────────────────────────────┐
│ PO #1002 - ABC Rice Supplier       │
├────────────────────────────────────┤
│ Order Date: Feb 03, 2026           │
│ Status: ✅ Received (Feb 10)       │
│ Total Amount: ₱75,000              │
│ Expected Delivery: Feb 10, 2026    │
│                                    │
│ Notes: Urgent delivery needed      │
│                                    │
├────────────────────────────────────┤
│ ITEMS IN ORDER:                    │
│                                    │
│ ┌──────────────────────────────┐   │
│ │Product|Qty|Unit Price|Total|x│   │
│ ├──────────────────────────────┤   │
│ │Premium (A1)|100|₱20|₱2,000|❌│   │
│ │Regular (B)|50|₱15|₱750|❌   │   │
│ │Select (C)|30|₱25|₱750|❌    │   │
│ └──────────────────────────────┘   │
│ [+ Add Item]                       │
│                                    │
│ Order Total: ₱3,500                │
│                                    │
├────────────────────────────────────┤
│ ACTIONS:                           │
│ [Mark as Received] [Edit Notes]    │
│ [Delete PO] [Close]                │
└────────────────────────────────────┘
```

**Features**:
- **PO Header Info**: ID, Supplier, Order Date, Status Badge, Total, Expected Delivery
- **Notes Display**: Read-only or editable (toggle with pencil icon)
- **Items Table**: 
  - Shows: Product (Variety/Grade), Quantity, Unit Price, Subtotal
  - Delete button (❌) for each item
  - Delete only works if PO status = "Pending"
- **Add Item Button**: Shows only if status = "Pending", opens add item form
- **Action Buttons**:
  - **Mark as Received** (if status ≠ Received): Shows confirmation modal, updates status to "received", sets received_date to TODAY
  - **Edit Notes**: Toggles notes field between display/edit mode
  - **Delete PO** (if status = Pending only): Asks confirmation, removes entire PO
  - **Close**: Closes modal/returns to list

**Status Rules**:
- **Pending**: Can edit items, add/remove, edit notes, create new items
- **Ordered**: Can view, mark as received, edit notes only
- **Received**: Read-only view only, can view audit trail (who marked received, when)
- **Cancelled**: Read-only view only

---

## PAGE 5: SUPPLIER PRODUCT RATINGS
**Location**: Section within Supplier Detail Page or dedicated tab  
**Purpose**: Track quality/delivery/price performance per supplier per product  
**Access**: Owner only

### Layout:
```
┌─────────────────────────────────────┐
│ SUPPLIER PRODUCT RATINGS            │
│ ABC Rice Supplier                   │
├─────────────────────────────────────┤
│ Add New Rating:                     │
│ Product: [Dropdown]                 │
│ Quality: ⭐⭐⭐⭐⭐ (1-5 scale)       │
│ Delivery: ⭐⭐⭐⭐☆ (1-5 scale)      │
│ Price: ⭐⭐⭐⭐⭐ (1-5 scale)        │
│ Comments: [Text area - optional]    │
│ [Save Rating] [Cancel]              │
│                                     │
├─────────────────────────────────────┤
│ Historical Ratings:                 │
│                                     │
│ Product: Premium Rice (A1)          │
│ ┌─────────────────────────────────┐ │
│ │ Quality: ⭐⭐⭐⭐⭐ (5/5)         │ │
│ │ Delivery: ⭐⭐⭐⭐☆ (4/5)        │ │
│ │ Price: ⭐⭐⭐⭐⭐ (5/5)          │ │
│ │ Overall: ⭐⭐⭐⭐⭐ (4.7/5)       │ │
│ │ Comments: Excellent quality,    │ │
│ │ slightly late on last shipment  │ │
│ │ Last rated: Mar 15, 2026        │ │
│ │ [Edit] [Delete]                 │ │
│ └─────────────────────────────────┘ │
│                                     │
│ Product: Regular Rice (B)           │
│ ┌─────────────────────────────────┐ │
│ │ Quality: ⭐⭐⭐⭐☆ (4/5)         │ │
│ │ Delivery: ⭐⭐⭐⭐⭐ (5/5)        │ │
│ │ Price: ⭐⭐⭐☆☆ (3/5)           │ │
│ │ Overall: ⭐⭐⭐⭐☆ (4.0/5)       │ │
│ │ Comments: Good but pricier than │ │
│ │ competitors                     │ │
│ │ Last rated: Mar 01, 2026        │ │
│ │ [Edit] [Delete]                 │ │
│ └─────────────────────────────────┘ │
│                                     │
└─────────────────────────────────────┘
```

**Features**:
- **Add New Rating Section** (Top, collapsible or always visible):
  - Product dropdown (shows products previously ordered from this supplier)
  - Star rating system (1-5 stars, clickable):
    - Quality: Condition of product received
    - Delivery: On-time and condition of delivery
    - Price: Cost competitiveness
  - Comments field (optional, for specific feedback)
  - [Save Rating] button saves to `supplier_product_ratings` table
  - [Cancel] clears form

- **Ratings Display** (Historical):
  - Groups ratings by product
  - Shows: All 3 ratings (quality/delivery/price) as star ratings
  - Shows: Overall rating (average of 3 ratings)
  - Shows: Comments (if any)
  - Shows: Last rated date
  - Shows: [Edit] & [Delete] buttons (edit updates record, delete removes rating entry)

- **Edit Mode**:
  - Clicking [Edit] makes stars clickable again
  - Shows [Save Changes] and [Cancel] buttons
  - Updates existing rating record

---

## PAGE 6: PAYABLES SUMMARY (INTEGRATION)
**Location**: Widget/section within Supplier Detail Page  
**Purpose**: Quick view of financial relationship with supplier  
**Access**: Owner only (also visible to Admin, but not editable)

### Layout:
```
┌─────────────────────────────────┐
│ PAYABLES TO THIS SUPPLIER       │
├─────────────────────────────────┤
│ Total Outstanding: ₱125,000     │
│ Paid This Month (Mar): ₱25,000  │
│ Last Payment: Mar 15, 2026      │
│ Next Due: Mar 31, 2026          │
│                                 │
│ [View All Payables] [Pay Now]   │
└─────────────────────────────────┘
```

**Features**:
- **Quick Metrics**: 
  - Outstanding balance (from account_payable table)
  - Month-to-date payments
  - Last payment date
  - Next due date
- **[View All Payables]**: Links to supplier_payables.php filtered by this supplier_id
- **[Pay Now]**: Opens payment form (redirects to payment system)

---

## PAGE 7: SUPPLIER LIST WITH METRICS DASHBOARD
**URL**: `/capstone/owner/suppliers.php` (Enhanced version)  
**Purpose**: Owner dashboard for supplier management  
**Access**: Owner only

### Top Dashboard Panel:
```
┌───────────────────────────────────────────────────┐
│ SUPPLIER MANAGEMENT DASHBOARD                     │
├───────────┬───────────┬───────────┬───────────────┤
│ Total     │ Active    │ Inactive  │ Avg Rating    │
│ 15        │ 12        │ 3         │ 4.2 ⭐        │
├───────────┴───────────┴───────────┴───────────────┤
│ Outstanding Payables: ₱425,000                     │
│ Pending POs: 5 | Last 30-day orders: 23            │
└───────────────────────────────────────────────────┘
```

**Features**:
- Quick stats cards at top
- Search/filter bar
- Supplier table with inline actions
- Bulk actions (select multiple, deactivate all, etc.)

---

## NAVIGATION FLOW DIAGRAM

```
LOGIN (Role Check)
    │
    ├─→ Owner
    │     │
    │     └─→ Main Dashboard
    │           │
    │           └─→ Finance Menu ▼
    │                 │
    │                 ├─→ [Suppliers] ← Supplier List Page
    │                 │     │
    │                 │     └─→ Supplier Detail Page
    │                 │           │
    │                 │           ├─→ View/Edit Supplier Info
    │                 │           │
    │                 │           ├─→ Purchase Orders Section
    │                 │           │     │
    │                 │           │     └─→ Create New PO
    │                 │           │           │
    │                 │           │           └─→ Add Products to Order
    │                 │           │
    │                 │           ├─→ View PO Detail
    │                 │           │     ├─→ Mark as Received
    │                 │           │     ├─→ Edit Items
    │                 │           │     └─→ Delete PO
    │                 │           │
    │                 │           ├─→ Product Ratings
    │                 │           │     ├─→ Add Rating
    │                 │           │     ├─→ Edit Rating
    │                 │           │     └─→ Delete Rating
    │                 │           │
    │                 │           └─→ Payables Summary
    │                 │                 └─→ View All Payables
    │                 │
    │                 ├─→ [Purchase Orders] ← PO List Page (All Suppliers)
    │                 │     │
    │                 │     ├─→ View PO Detail
    │                 │     ├─→ Create New PO (+ Supplier Selection)
    │                 │     └─→ Filter by Supplier
    │                 │
    │                 └─→ [Payables] ← Payables Page
    │                       └─→ View/Pay invoices
    │
    ├─→ Admin
    │     └─→ Different Dashboard (NO supplier access)
    │
    └─→ Cashier
          └─→ Different Dashboard (NO supplier access)
```

---

## BUTTON BEHAVIORS & USER CONFIRMATIONS

### Confirmation Dialogs (Modal Alerts):

**1. Mark PO as Received**
```
Title: Confirm Receipt
Message: "Mark PO #1002 as received? This action cannot be undone."
Buttons: [Yes, Confirm] [No, Cancel]
On Confirm: 
  - Updates status to "received"
  - Sets received_date to TODAY
  - Page refreshes or modal re-displays
Success Message: "✓ PO #1002 marked as received on Mar 20, 2026"
```

**2. Delete PO**
```
Title: Delete Purchase Order
Message: "Delete PO #1003? All items will be removed. This action cannot be undone."
Show: Only if status = "Pending"
Buttons: [Delete] [Cancel]
On Confirm: 
  - Deletes PO from purchase_orders table
  - Deletes associated items from purchase_order_items
  - Returns to supplier detail or PO list
Success Message: "✓ PO #1003 deleted successfully"
```

**3. Remove Item from PO**
```
Title: Remove Item
Message: "Remove [Product Name] from this order?"
Show: Only if PO status = "Pending"
Buttons: [Remove] [Cancel]
On Confirm:
  - Deletes row from purchase_order_items
  - Recalculates order total
  - Refreshes items table without page reload
Success: Item disappears from table
```

**4. Deactivate Supplier**
```
Title: Deactivate Supplier
Message: "Deactivate ABC Rice Supplier? They will no longer appear in purchase order selection."
Buttons: [Deactivate] [Cancel]
On Confirm:
  - Sets supplier status to "inactive"
  - Still keeps historical data (POs, ratings, payables)
  - Can be reactivated later
Success Message: "✓ Supplier deactivated"
```

**5. Delete Rating**
```
Title: Delete Rating
Message: "Delete this rating? Historical data will be removed."
Buttons: [Delete] [Cancel]
On Confirm:
  - Deletes row from supplier_product_ratings
  - Rating disappears from list
Success: Rating removed from display
```

### Success Notifications:
- Green toast notification (top-right, auto-disappear in 3 seconds)
- Format: "✓ [Action completed]"

### Error Notifications:
- Red alert box or toast
- Format: "❌ Error: [Specific reason]"
- Don't auto-close (user must acknowledge)

---

## UX BEST PRACTICES IMPLEMENTED

| Feature | Purpose | Implementation |
|---------|---------|-----------------|
| **Breadcrumbs** | Show user location | Suppliers > Detail > Edit |
| **Back Button** | Quick navigation | "← Back to Suppliers" at top |
| **Loading States** | Visual feedback | Spinners on AJAX calls, disabled buttons |
| **Inline Editing** | Reduce page loads | Edit mode toggles without navigation |
| **Confirmation Dialogs** | Prevent accidents | All destructive actions ask for confirmation |
| **Empty States** | User guidance | "No suppliers added yet. Create one →" |
| **Error Messages** | User clarity | Specific errors, not generic "Something went wrong" |
| **Keyboard Shortcuts** | Power users | ESC to close modals, Enter to submit forms |
| **Responsive Design** | Mobile-friendly | Tables stack on mobile, buttons remain clickable |
| **Status Badges** | Quick scanning | Color-coded (Active=Green, Inactive=Gray, etc.) |
| **Action Buttons Order** | Intuitive flow | Primary actions first, destructive actions last |
| **Form Validation** | Error prevention | Show errors before submit, highlight required fields |
| **Auto-calculations** | Reduce errors | Subtotal, order total auto-calculated |

---

## DATABASE INTEGRATION POINTS

### Table: `suppliers`
- **Used for**: Supplier list, detail view, dropdown in PO creation
- **Fields displayed**: supplier_id, name, contact_person, phone, email, address, status, created_at
- **Operations**: SELECT (list, detail), INSERT (add supplier), UPDATE (edit, deactivate), DELETE (optional purge)

### Table: `purchase_orders`
- **Used for**: PO list, supplier POs section, PO detail view
- **Fields displayed**: po_id, supplier_id, order_date, status, total_amount, notes, received_date
- **Operations**: SELECT (list, detail), INSERT (create PO), UPDATE (mark received, update notes), DELETE (cancel pending POs)
- **Status values**: pending, ordered, received, cancelled

### Table: `purchase_order_items`
- **Used for**: Items display in PO, add/remove items functionality
- **Fields displayed**: po_item_id, po_id, product_id, quantity, price_per_unit, subtotal
- **Operations**: SELECT (show items), INSERT (add item), UPDATE (edit qty/price), DELETE (remove item)

### Table: `supplier_product_ratings`
- **Used for**: Product ratings section, supplier average rating calculation
- **Fields displayed**: rating_id, supplier_id, product_id, quality_rating, delivery_rating, price_rating, overall_rating, comments, last_updated
- **Operations**: SELECT (display ratings), INSERT (add rating), UPDATE (edit rating), DELETE (remove rating)
- **Calculation**: overall_rating = (quality + delivery + price) / 3

### Table: `account_payable`
- **Used for**: Payables summary widget, payment history
- **Fields displayed**: payable_id, supplier_id, amount, paid_date, due_date, status
- **Operations**: SELECT (outstanding balance, last payment, next due date)
- **Integration**: Read-only in supplier detail, links to full payables page

### Table: `inventory_transactions` (Optional)
- **Used for**: Audit trail (future feature)
- **Fields**: Received quantity validation, expected vs actual received
- **Future**: "Mark PO Received" could create inventory_transaction record

---

## SECURITY & VALIDATION

### Server-Side (PHP):
```
✓ Session check: if($_SESSION['role'] !== 'owner') → deny
✓ Input validation: int, string, decimal type checks
✓ Prepared statements: All SQL queries use ? placeholders
✓ HTML escaping: htmlspecialchars() on all output
✓ CSRF protection: Form tokens on all state-changing operations
✓ Authorization: Verify resource ownership before update/delete
✓ Logging: Record all PO status changes, supplier edits
```

### Client-Side (JavaScript):
```
✓ Form validation: Check required fields before AJAX
✓ Numeric validation: Quantity > 0, Price >= 0
✓ Confirmation dialogs: Prevent accidental deletes
✓ Loading indicators: Show during AJAX operations
✓ Disable buttons: Prevent double-submissions
✓ XSS prevention: Use textContent not innerHTML for user data
```

---

## IMPLEMENTATION PRIORITY

### Phase 1 (MVP - Essential):
1. ✅ Supplier List Page (view, search, filter)
2. ✅ Supplier Detail Page (info cards, edit)
3. ✅ Purchase Orders Section (create, view, mark received)
4. ✅ Add/Remove Items in PO
5. Status badges and confirmation dialogs

### Phase 2 (Enhancement):
1. Product ratings system UI
2. Payables integration widget
3. Bulk supplier actions
4. Advanced filtering (by rating, by payables)

### Phase 3 (Polish):
1. PDF export for POs
2. Supplier comparison reports
3. Performance analytics
4. Automated low-stock alerts per supplier

---

## TESTING CHECKLIST

### Functional Testing:
- [ ] Create supplier → displays in list
- [ ] Edit supplier → updates immediately
- [ ] Deactivate supplier → removes from PO dropdown
- [ ] Create PO → items save correctly
- [ ] Add item → item appears in table, total updates
- [ ] Remove item → item deleted, total updates
- [ ] Mark received → status changes, date sets
- [ ] Add rating → displays in ratings section
- [ ] Edit rating → updates existing record
- [ ] Delete rating → removes from list

### Security Testing:
- [ ] Non-owner cannot access suppliers.php (redirects)
- [ ] Cannot modify others' data via URL manipulation
- [ ] Cannot submit duplicate items in same PO
- [ ] Cannot mark a PO received twice

### UI/UX Testing:
- [ ] All buttons responsive on mobile
- [ ] Forms validate before submission
- [ ] Error messages clear and specific
- [ ] Modals close properly
- [ ] Tables sortable and filterable
- [ ] Navigation breadcrumbs work correctly

---

## NEXT STEPS FOR DEVELOPMENT

1. **Create suppliers.php**: List page with table, search, filters
2. **Create supplier_detail.php**: Detail view with all sections
3. **Enhance purchase_orders.php**: Already has modal, refine item management
4. **Create get_products.php**: For dropdown (already created ✓)
5. **Create add_po_item.php**: Backend (already created ✓)
6. **Create delete_po_item.php**: Backend (already created ✓)
7. **Create supplier_product_ratings UI**: Add rating form + display grid
8. **Add AJAX endpoints**: Update PO status, edit supplier, delete supplier
9. **Add validation & error handling**: Server-side validation for all operations
10. **Test role-based access**: Ensure only owner can access

---

**Document Version**: 1.0  
**Last Updated**: March 20, 2026  
**Status**: Ready for Implementation
