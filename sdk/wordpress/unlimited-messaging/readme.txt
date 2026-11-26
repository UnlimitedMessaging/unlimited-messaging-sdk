=== Unlimited Messaging: Order Notifications & Form Alerts ===
Contributors: ilianazz
Tags: whatsapp, sms, woocommerce, order notifications, contact form
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WooCommerce order updates and form alerts by WhatsApp, SMS and email, from your own number and mailbox. One account, no Twilio, no Meta templates.

== Description ==

Your customers read WhatsApp and SMS, not order emails. Unlimited Messaging sends your WooCommerce order updates and your contact form alerts on **WhatsApp, SMS and email at once**, from **your own WhatsApp number, your own phone and your own mailbox**.

Other plugins make you open three accounts to do this: Twilio for SMS, Meta's WhatsApp Business API (with message templates to get approved and a fee per conversation) and an SMTP service for email. Here it is **one account, one API key, three channels**.

= What it does =

* **New order alert on your phone.** Every new order lands on your WhatsApp the second it is placed, with the total, the customer and the items.
* **Order updates for your customers.** Paid, on hold, shipped, cancelled, refunded, payment failed, and every note you add to an order.
* **All channels in one go.** Tick WhatsApp, SMS and email on the same notification: if the customer misses one, the others still get there.
* **Form alerts.** Contact Form 7, WPForms, Gravity Forms, Elementor Pro, Fluent Forms and Ninja Forms submissions sent straight to your phone.
* **Your words.** Edit every message with placeholders (`{customer_first_name}`, `{order_number}`, `{order_total}`, `{order_url}`...) or keep the ready-made texts, translated into your site language.
* **Never slows your checkout.** Messages go out in the background through WooCommerce's Action Scheduler.
* **Safe for your number.** An hourly cap you choose, each update sent at most once per order, and order messages queued ahead of any bulk send.
* **See what went out.** An activity log in WordPress, and the full history with delivery status in your Unlimited Messaging dashboard.

= Your own channels =

* **WhatsApp**: link your number (business or personal) by scanning a QR code, like WhatsApp Web. No Meta Business verification, no template approval, no per-message fee.
* **SMS**: install the free Unlimited Messaging Android app on a phone; texts go out from its SIM card at your carrier's price.
* **Email**: connect your mailbox (Gmail, Outlook, OVH, any IMAP/SMTP), so emails come from your real address.

= Pricing =

The plugin is free. Messages are sent through your [Unlimited Messaging](https://unlimitedmessaging.app/wordpress?utm_source=wordpress&utm_medium=readme&utm_campaign=wordpress_plugin) account:

* **Free plan**: up to 50 recipients, with a short "Sent with UnlimitedMessaging.app" line at the end of each text message.
* **Paid plans**: unlimited recipients and no added line. See [pricing](https://unlimitedmessaging.app/pricing?utm_source=wordpress&utm_medium=readme&utm_campaign=wordpress_plugin).

There is never a per-message fee from Unlimited Messaging.

= Good to know =

WhatsApp is linked as a device on your own number, like WhatsApp Web. Use it for order updates and replies to your customers, not for unsolicited bulk messages: WhatsApp can restrict numbers that spam. The hourly cap is there to help.

== External services ==

This plugin connects to the Unlimited Messaging API (`https://api.unlimitedmessaging.app`), run by Unlimited Messaging, to send your notifications. Nothing is sent until you add your API key.

* **When**: when you open the settings (to list your connected accounts), when you send a test, and each time an enabled notification is triggered (an order changes status, a note is added to an order, a form is submitted).
* **What**: your API key, the recipient's phone number and/or email address, and the message text you configured (which can include the customer's name, order number, total, items, address and order link, or the form fields). The plugin, WordPress and WooCommerce versions are sent in the User-Agent.
* **Terms of use**: https://unlimitedmessaging.app/legal
* **Privacy policy**: https://unlimitedmessaging.app/privacy

== Installation ==

1. Install and activate the plugin from **Plugins > Add New**.
2. [Create a free Unlimited Messaging account](https://app.unlimitedmessaging.app/login?utm_source=wordpress&utm_medium=readme&utm_campaign=wordpress_plugin) and connect your channels: scan a QR code for WhatsApp, sign in to your mailbox, or install the Android app for SMS.
3. Create an API key on the **API keys** page of your dashboard.
4. In WordPress, open **Messaging**, paste the key, add your phone number and save.
5. Click **Send test message**, then pick your notifications in the **WooCommerce** and **Forms** tabs.

== Frequently Asked Questions ==

= Do I need a WhatsApp Business API account, Twilio or Meta approval? =

No. You link your existing WhatsApp number by scanning a QR code. There are no message templates to submit and no per-conversation fee.

= Does it work without WooCommerce? =

Yes. The form alerts work on any WordPress site with one of the supported form plugins. The WooCommerce tab appears once WooCommerce is active.

= Which phone format do my customers need to type? =

Any. The plugin turns "06 12 34 56 78" with a French billing address into +33612345678 using the order's billing country.

= Will the customer get the message twice if an order goes back and forth between statuses? =

No. Each order update is sent at most once per order. Notes to the customer are the exception: each note is its own message.

= Can I send on several channels? =

Yes, tick as many channels as you want on each notification. One message goes out per channel, all at the same time.

= Does it replace WooCommerce emails? =

No, WooCommerce keeps sending its own emails. Use the email channel here only if you want a short message from your own mailbox on top of them.

= Can I change the text from code? =

Yes, with the `unlimited_messaging_order_text` and `unlimited_messaging_form_text` filters.

== Screenshots ==

1. Connection: paste your API key and pick your WhatsApp number, phone and mailbox.
2. WooCommerce: choose each update, who gets it and on which channels.
3. Form alerts for Contact Form 7, WPForms, Gravity Forms and more.
4. Activity log.

== Changelog ==

= 1.0.0 =
* First release: WooCommerce order notifications (new order, processing, on hold, completed, cancelled, refunded, failed, customer note) and form alerts, on WhatsApp, SMS and email.
