WordPress eCommerce Checkout Finland maksutapa
==============================================

Checkout Finland maksutapa WordPressin eCommerce pluginiin 

Asennus:

Kopioi checkoutfinland.php tiedosto wordpress/wp-content/plugins/wp-e-commerce/wpsc-merchants kansioon
	
Aseta kauppiastunnus, turva-avain ja toimitusaika kaupan maksutapa asetuksista (wp admin puolella)
	
Voit testata asennusta testitunnuksilla

	Kauppiastunnus: 375917 
	Turva-avain: SAIPPUAKAUPPIAS

Maksutapahtumaa luodessa moduli ottaa yhteyttä Checkout:n palvelimiin ja lähettää maksutapahtuman tiedot, jos palvelimesi estää esim. palomuurin toimesta yhteydet ulkomaailmaan moduli ei toimi. Varmista myös, että palvelimesi PHP:n asetuksissa on allow_url_fopen sallittu tai että palvelimellesi on asennettu cURL ja PHP:n tarvitsemat cURL kirjastot.



Checkout Finland payment method for WordPress eCommerce plugin
==============================================================

Installation:
	- Copy the checkoutfinland.php file to wordpress/wp-content/plugins/wp-e-commerce/wpsc-merchant folder
	- Set your merchant id, security key and delivery time in eCommerce payment method settings page

Testing:
	- You can test the installation by using the following merchant id and security key

	Merchant id: 375917
	Security key: SAIPPUAKAUPPIAS


When creating a payment this plugin makes a request to Checkout Finland servers so if the plugin doesn't work make sure your server doesn't have a firewall blocking outgoing connections and that you have allow_url_fopen allowed in your PHP settings or that your PHP installation can use cURL to make the request.