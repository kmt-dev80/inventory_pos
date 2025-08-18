# Inventory & POS Management System 📦🛒

<img width="1346" height="600" alt="inventory" src="https://github.com/user-attachments/assets/036a5ef5-4439-4641-8b9c-e19e15e60761" />

## Overview
A web-based **Inventory and Point of Sale (POS) Management System** built with **PHP, MySQL, JavaScript, HTML, CSS, and Bootstrap**.  
The system follows the **FIFO (First-In-First-Out)** inventory method and provides **advanced reporting features** for better business insights.

---

## ✨ Features

### 🔑 Authentication & Security
- Secure login & logout system  
- Role-based access control (Admin, Cashier, Staff)  
- Session validation & CSRF protection  

### 📦 Inventory Management
- Add, update, and delete products  
- Categorization (Category, Subcategory, Brand, etc.)  
- FIFO stock management (old stock is sold first)  
- Barcode-based stock handling (retain existing product or add new product)  
- Stock logs with **before and after quantity**  

### 🛒 POS (Point of Sale)
- Multiple product sales in one invoice  
- Invoice-based transactions with unique IDs  
- Automatic stock deduction (FIFO method)  
- Daily sales summary report  
- Support for discounts & multiple payment methods  

### 📊 Advanced Reports
- Sales report with date filters  
- Stock movement history (in/out with before & after stock)  
- Product-wise, category-wise, and daily/weekly/monthly reports  
- Export options: **Excel & PDF**  

### ⚡ Additional Features
- Responsive design (desktop, tablet, mobile)  
- Search by product, invoice, or category  
- Environment-based config (`config.php` with constants & DB credentials)  
- User-friendly dashboard with statistics  

---

## 🛠️ Technologies Used

### Frontend
- HTML5, CSS3, Bootstrap 5  
- JavaScript (with jQuery for dynamic UI)  

### Backend
- PHP (Core + OOP concepts)  
- MySQL (Relational Database with optimized schema)  

### Reports & Export
- PHP libraries for **PDF & Excel export**  
- Date filtering with advanced queries  

---

## 🚀 How to Use

### For Admin
1. Log in with admin credentials  
2. Add categories, brands, and products  
3. Monitor stock logs & FIFO-based stock movements  
4. Generate advanced sales and stock reports  

### For Cashier
1. Log in via cashier account  
2. Create invoices and process sales  
3. Print/download invoices for customers  
4. Check daily sales summary  

### For Manager/Viewer
1. Access reports and statistics  
2. Track product movement & stock history  
3. Export reports in **Excel/PDF**  

### Here is my site link =>  http://inventory-pos.wdpfctg.site/

---
## License 📄

MIT © [Takiul Hasan](https://github.com/kmt-dev80)
