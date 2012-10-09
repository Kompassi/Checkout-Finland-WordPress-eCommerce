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