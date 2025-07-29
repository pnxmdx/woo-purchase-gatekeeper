Woo Purchase Gatekeeper 2.0
Restrict access to WordPress pages until a user has made a WooCommerce purchase.
Supports global rules, per‑page rules, custom “Access Denied” behavior, and access logging.

✨ Features
Restrict by:

✅ Any purchase

✅ Specific products (comma‑separated IDs)

✅ Per‑page rules (via meta box in Page editor)

Choose how to handle denied access:

Redirect to a custom page

Show a custom “Access Denied” message

Built‑in rich‑text editor for denied message

Logs denied access attempts:

User ID & email

Page title

Date & time

Shortcodes for conditional content:

[show_if_purchased_anything]Your content[/show_if_purchased_anything]

[show_if_purchased_any products="123,456"]Your content[/show_if_purchased_any]

📦 Installation
Download the plugin from GitHub (Code → Download ZIP).

In your WordPress admin:

Go to Plugins → Add New → Upload Plugin

Upload the ZIP and click Activate

Go to WooCommerce → Purchase Access to configure.

⚙️ Settings
Navigate to WooCommerce → Purchase Access and configure:

Restriction Mode

Any purchase

Specific product(s)

Per‑page rules

Restricted Page (if global)
Select which page should be restricted under global mode.

Required Product IDs
Comma‑separated list of WooCommerce product IDs.

Deny Action

Redirect → send users to a custom page

Message → display a custom message on the page

Redirect URL
Where unauthorized users should be sent.

Access Denied Message
Customize what users see when denied.

📝 Per‑Page Rules
When Per‑Page Rules mode is enabled:

Edit any Page

In the sidebar meta box Purchase Gatekeeper, enter product IDs

Only users who purchased those products will have access

📊 Access Logs
View logs under WooCommerce → Access Logs

Each denied attempt records:

User ID and email

Restricted page

Timestamp

🚀 Shortcodes
Restrict inline content to any purchaser:

shortcode
Copy
Edit
[show_if_purchased_anything]
This text is visible only to customers who have purchased anything.
[/show_if_purchased_anything]
Restrict inline content to buyers of specific products:

shortcode
Copy
Edit
[show_if_purchased_any products="123,456"]
This content is for buyers of product 123 or 456.
[/show_if_purchased_any]
📌 Roadmap
 Role-based access after purchase

 Subscription compatibility

 Gutenberg blocks for conditional content

 CSV export of access logs

👨‍💻 Author
Built for WordPress + WooCommerce by pnxmdx
