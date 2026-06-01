import { MigrateUpArgs, MigrateDownArgs, sql } from '@payloadcms/db-postgres'

export async function up({ db, payload, req }: MigrateUpArgs): Promise<void> {
  await db.execute(sql`
   CREATE TYPE "public"."enum_site_settings_quote_page_ui_workflow_items_icon_name" AS ENUM('file-text', 'mail-check', 'message-circle');
  CREATE TABLE "site_settings_quote_page_ui_workflow_items" (
	"_order" integer NOT NULL,
	"_parent_id" integer NOT NULL,
	"id" varchar PRIMARY KEY NOT NULL,
	"icon_name" "enum_site_settings_quote_page_ui_workflow_items_icon_name" DEFAULT 'file-text'
  );

  CREATE TABLE "site_settings_quote_page_ui_workflow_items_locales" (
	"title" varchar DEFAULT '',
	"text" varchar DEFAULT '',
	"id" serial PRIMARY KEY NOT NULL,
	"_locale" "_locales" NOT NULL,
	"_parent_id" varchar NOT NULL
  );

  ALTER TABLE "site_settings" ADD COLUMN "contact_page_ui_hero_image" varchar DEFAULT '/images/global-network-blue.jpg';
  ALTER TABLE "site_settings" ADD COLUMN "quote_page_ui_hero_image" varchar DEFAULT '/images/cta-lift-photo.png';
  ALTER TABLE "site_settings" ADD COLUMN "quote_page_ui_product_link_href" varchar DEFAULT '/products';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_label" varchar DEFAULT 'Contact Us';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_title" varchar DEFAULT 'Talk to BOB-LIFT before choosing a crane.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_hero_description" varchar DEFAULT 'For model selection, truck chassis matching, marine installation and export questions, use the direct contact channels or create a structured inquiry draft.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_phone" varchar DEFAULT 'Phone';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_whatsapp" varchar DEFAULT 'WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_card_labels_address" varchar DEFAULT 'Address';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_whatsapp_value" varchar DEFAULT 'Start chat';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_title" varchar DEFAULT 'WeChat';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_description_template" varchar DEFAULT 'Scan the QR code or search ID {wechatId}. The QR block is kept as a compact card so it does not dominate mobile screens.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "contact_page_ui_we_chat_qr_alt" varchar DEFAULT 'BOB-LIFT WeChat QR code';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_title" varchar DEFAULT 'Request a tailored BOB-LIFT quotation.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_hero_description" varchar DEFAULT 'Share your product interest, destination country, contact details and working conditions. The team can then match a practical lifting solution for your project.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_workflow_label" varchar DEFAULT 'Quote Workflow';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_workflow_title" varchar DEFAULT 'Fill once, send through your preferred channel.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_page_ui_product_link_label" varchar DEFAULT 'Need to choose a model first?';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_label" varchar DEFAULT 'Request Quote';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_title" varchar DEFAULT 'Tell us your lifting requirement.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_badge" varchar DEFAULT '24h reply';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_name" varchar DEFAULT 'Full Name';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_phone" varchar DEFAULT 'Phone / WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_country" varchar DEFAULT 'Country / Region';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_product" varchar DEFAULT 'Product Interest';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_product_not_decided" varchar DEFAULT 'Not decided yet';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_quantity" varchar DEFAULT 'Expected Quantity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_message" varchar DEFAULT 'Project Details';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_fields_message_placeholder" varchar DEFAULT 'Load capacity, working radius, truck chassis, destination port, deadline...';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_save" varchar DEFAULT 'Save Inquiry Draft';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_saving" varchar DEFAULT 'Saving Inquiry...';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_email" varchar DEFAULT 'Send Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_buttons_whatsapp" varchar DEFAULT 'WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_success_title" varchar DEFAULT 'Inquiry saved in the admin.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_success_text" varchar DEFAULT 'Use Email or WhatsApp if you also want to send the draft through your preferred app.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_messages_error_fallback" varchar DEFAULT 'Inquiry could not be saved.';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_name" varchar DEFAULT 'Name';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_email" varchar DEFAULT 'Email';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_phone" varchar DEFAULT 'Phone / WhatsApp';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_country" varchar DEFAULT 'Country';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_product" varchar DEFAULT 'Product';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_quantity" varchar DEFAULT 'Quantity';
  ALTER TABLE "site_settings_locales" ADD COLUMN "quote_form_ui_inquiry_line_labels_message" varchar DEFAULT 'Project details';
  ALTER TABLE "site_settings_quote_page_ui_workflow_items" ADD CONSTRAINT "site_settings_quote_page_ui_workflow_items_parent_id_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings"("id") ON DELETE cascade ON UPDATE no action;
  ALTER TABLE "site_settings_quote_page_ui_workflow_items_locales" ADD CONSTRAINT "site_settings_quote_page_ui_workflow_items_locales_parent_fk" FOREIGN KEY ("_parent_id") REFERENCES "public"."site_settings_quote_page_ui_workflow_items"("id") ON DELETE cascade ON UPDATE no action;
  CREATE INDEX "site_settings_quote_page_ui_workflow_items_order_idx" ON "site_settings_quote_page_ui_workflow_items" USING btree ("_order");
  CREATE INDEX "site_settings_quote_page_ui_workflow_items_parent_id_idx" ON "site_settings_quote_page_ui_workflow_items" USING btree ("_parent_id");
  CREATE UNIQUE INDEX "site_settings_quote_page_ui_workflow_items_locales_locale_pa" ON "site_settings_quote_page_ui_workflow_items_locales" USING btree ("_locale","_parent_id");`)
}

export async function down({ db, payload, req }: MigrateDownArgs): Promise<void> {
  await db.execute(sql`
   DROP TABLE "site_settings_quote_page_ui_workflow_items" CASCADE;
  DROP TABLE "site_settings_quote_page_ui_workflow_items_locales" CASCADE;
  ALTER TABLE "site_settings" DROP COLUMN "contact_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "quote_page_ui_hero_image";
  ALTER TABLE "site_settings" DROP COLUMN "quote_page_ui_product_link_href";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_whatsapp";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_card_labels_address";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_whatsapp_value";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_description_template";
  ALTER TABLE "site_settings_locales" DROP COLUMN "contact_page_ui_we_chat_qr_alt";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_hero_description";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_workflow_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_workflow_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_page_ui_product_link_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_label";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_badge";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_name";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_country";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_product";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_product_not_decided";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_quantity";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_message";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_fields_message_placeholder";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_save";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_saving";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_buttons_whatsapp";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_success_title";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_success_text";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_messages_error_fallback";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_name";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_email";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_phone";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_country";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_product";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_quantity";
  ALTER TABLE "site_settings_locales" DROP COLUMN "quote_form_ui_inquiry_line_labels_message";
  DROP TYPE "public"."enum_site_settings_quote_page_ui_workflow_items_icon_name";`)
}
