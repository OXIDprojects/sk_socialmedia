/**** Titel ****/
Social Media Sharings fuer OXID ab 4.6.0

/**** Autor ****/
Steve Knornschild

/**** Prefix ****/
sk

/**** Version ****/
1.0

/**** Link ****/
keine Gewerbliche Seite fuer Modulvertrieb vorhanden, aber unser Shop ist unter http://www.sitzdesign.de zu finden

/**** Mail ****/
knornschild@sitzdesign.de

/**** Beschreibung ****/
Modul um neue Artikel automatisch auf Facebook in einer eingestellten Gruppe oder der Firmenseite zu posten.
Twitter ist noch geplant


/**** Beschreibung ****/
1.	alles aus dem ordner copy_this in den Shop root hochladen
2.	in die config.inc.php am Ende dies hinzufuegen:
	/* sk_socialmedia Settings Start */
	$this->groupId = null;
	
	$this->pageId = "<ID eurer Facebook page>";
	
	// webUrl is only needed if u want to post with another domain than the default shopdomain
	$this->webUrl = null;
	
	// To get this code paste this url to the browser
	// https://graph.facebook.com/oauth/authorize?client_id=<APP_ID>&scope=email,publish_stream,offline_access,manage_pages&redirect_uri=<$this->webUrl>&display=popup
	$this->authCode = "";
	
	$this->categoryId = null;
	
	$this->descLength = "300";
	/* sk_socialmedia Settings End */

3.	einen cronjob einrichten der autmatisch die cron.php im ordner <shopdir>/modules/sk_socialmedia/cron.php ausfuehrt.
	z.B. so:
	*/10       *       *       *       *       /var/www/vhosts/<shopdomain>/modules/sk_socialmedia/cron.php
4.	Unter Service->Tools->SQL Update dies ausfuehren:
	ALTER TABLE oxarticles add column fbpublished tinyint(3) DEFAULT '0' NOT NULL, add column smdontpublish tinyint(3) DEFAULT '1' NOT NULL
5.	Modul unter Einstellungen->Module das Modul aktivieren, tmp leeren und views aktualisieren
6.	nun kann in den Artikeldetails im neuen Tab Social Media eingestellt werden ob dieser Artikel auf facebook gepostet werden soll


/**** Libraries ****/
Facebook PHP SDK