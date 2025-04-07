# 6.3.22
- Bugfix: updateConsentMode nutzt die falsche Variable für Google Ad Permissions (GTM-GH-19)
- Änderung: Product im Datalayer über ReferenceID ziehen statt ID (GTM-GH-17)
- Bugfix: JS Error: auf Listenseite bei leerem Offcanvas WK (GTM-GH-16)

# 6.3.21
- Bugfix: Checkout Fehler für Custom Products

# 6.3.20
- Neu: mehr Produktdaten im transactionProducts Array (GTM-GH-10)
- Neu: PCI compliance (GTM-GH-12)
- Bugfix: Fehler beim Einsatz von Slidern mit leeren dynamischen Produktgruppen in Erlebniswelten (GTM-GH-12)

# 6.3.19
- Neu: Option um Produkt IDs (Shopware UUIDs) in ecommerce events zu includieren (GTM-GH-7)
- Neu: Parameter new_customer und customer_lifetime_value sind nun im purchase Event vorhanden (GTM-GH-3)
- New: visitorLifetimeOrderCount nun im Datalayer vorhanden (GTM-GH-6)
- New: Neue Remarketing Implementation (alte Version per Config Option verfügbar, wird bald entfernt) (CDVRS-49)
- New: view_item_list Events werden nun auch für Shopping Welten Elemente gefeuert (CDVRS-8)

# 6.3.18
- Neu: Leerzeichen bei mehreren GTM IDs trimmen (CDVRS-61)
- Neu: remove_from_wishlist wird nun auf Wunschlisten gefeuert (CDVRS-51)

# 6.3.17
- Google Ads Consent Einstellung ist nun unabhängig von Shopwares Google Ads Cookie (FD-33043)
- Neu: view_item_list wird nun auf Wunschlisten gefeuert (CDVRS-51)

# 6.3.16
- Neu: Wunschlisten Events (add/remove) auf Listen-/Detailseiten hinzugefügt
- Bugfix: Preis in Listen-Events (FD-33029)
- Bugfix: Kommentare in JS (CDVRS-56)

# 6.3.15
- Bugfix: Möglicher Fehler im Cart beim Benutzen von Gutscheinen

# 6.3.14
- Neu: select_item feuert nun auch nach Blättern im Listing (CDVRS-53)
- Neu: view_cart feuert nun auch beim Offcanvas WK (CDVRS-52)
- Bugfix: JS Fehler in Listings wenn Kaufen-Button deaktiviert (CDVRS-54)
- Bugfix: Möglicher Fehler beim dekodieren von json Daten

# 6.3.13
- Bugfix: Möglicher Bug mit paginierten Listing gefixt

# 6.3.12
- Neu: Plugin-Config nun auch auf Niederländisch verfügbar (CDVRS-34)
- Neu: GenericPageLoadedEvent als Event hinzugefügt (FD-32989)
- Neu: Mehr Daten im select_item Event (CDVRS-45)
- Bugfix: Preise in add_to_cart_list entsprechen nun der Config-Einstellung (CDVRS-46)

# 6.3.11
- Neu: Mehr Daten im remove_from_cart Event
- Bugfix: Noscript Code wird nun entfernt, wenn Plugin im Datalayer-Only Mode genutzt wird

# 6.3.10
- Neu: Consent Mode Update unmittelbar nach Änderung im Shopware Consent Manager, nicht erst nach Page-Reload (CDVRS-40)

# 6.3.9
- view_item_list-Event enthält nun auch die item_variant (CDVRS-36)
- view_item_list-Event enthält nun auch die item_category (CDVRS-36)
- kleinere Bugfixes für das view_item_list-Event (CDVRS-36)
- Indizes hinzugefügt für die Events view_cart, confirm_order und purchase. Indizes starten jetzt global bei 0 (CDVRS-38)

HINWEIS: Durch einen Bug in SW6.6.6.0 ist unser Plugin nicht kompatibel mit dieser Version. 
Bitte installieren Sie SW 6.6.6.1, welches bereits verfügbar ist.

# 6.3.8
- alte Snippet-/Übersetzungsdateien entfernt (CDVRS-35)

# 6.3.7
- Neu: item_variant Parameter ist nun in den Checkout Events enthalten (CDVRS-22)
- Neu: Option zum Hashen von Enhanced Conversion Daten vor dem Senden an Google (CDVRS-28)
- Update: CookieFirst Einbindung aktualisiert (CDVRS-26)
- Bugfix: Undefined array key "item_category" auf Detailseiten (CDVRS-30)

# 6.3.6
- Bugfix: Exception handling für Produkte ohne UUIDs (z.B. Gutscheine) (CDVRS-23)

# 6.3.5
- Nicht mehr benötigte Services für EE und Adwords entfernt

# 6.3.4
- Bugfix: Exception handling für Produkte ohne UUIDs (z.B. Pfand)
- Bugfix: Exception handling für Custom Produkte (SW Plugin SwagCustomizedProducts)

# 6.3.3
- Neu: value-Parameter in add_to_cart Event (CDVRS-1)
- Neu: Kategorie bei "add_to_cart" Event (CDVRS-11)
- Neu: Parent Product ID im Datalayer (CDVRS-13)
- Bugfix: Detail Page Template benutzt ab sofort das component/buy-widget/buy-widget-form.html.twig Template

# 6.3.2
- Bugfix: Fehlendes Semikolon im Remarketing Code (FD-32841)
- Bugfix: Laden der Kundengruppe (FD-32842)
- Bugfix: PHP Warning bei Gutschein Code Eingabe (SW-267207)
- Bugfix: add_to_cart_listing Daten wieder vollständig (SW-267320)
- PageHiding Option wurde entfernt (Diese Funktionalität wurde von Google eingestellt)
- Neu: Auswahl der Implementation des Consent Mode. Bitte überprüfen Sie Ihre Plugin Settings.

# 6.3.1
- Die von Shopware gewählte Implementation des Google Consent Mode V2 konnte unter Umständen dazu führen, dass die Verbindung GA und unserem Plugin nicht mehr hergestellt werden konnte. Dieses Update behebt das Problem. Bitte prüfen Sie nach dem Update, ob die Daten korrekt in Ihren Systemen ankommen und lesen Sie das Update unseres Blogeintrags: https://www.codiverse.de/google-consent-mode-2-0-infos-und-nutzung/

# 6.3.0
- SW6.6 Kompatibilität

# 6.2.10
- Bufix für Gutscheincodes auf Bestellabschluss-Seiten

# 6.2.9
- select_item feuert nun auch bei Klick auf den Detail Button im Listing
- Bugfix für Produkte ohne Hersteller
- Neu: Option um die Code-Verzögerung auf der Bestellabschluss-Seite zu umgehen

# 6.2.8
- anderen Twig-Block für select_item Event Daten verwendet

# 6.2.7
- Bugfix für die Ausgabe von Nettopreisen, wenn im Frontend ohnehin Nettopreise ausgegeben werden
- Anpassungen an das noscript-Tag bei Server-side-tagging (SST)
- Minimum SW Version auf 6.5.5.x angehoben

# 6.2.6
- JS-Dist-Dateien aktualisieren

# 6.2.5
- neue Plugin Option: CookieFirst Kompatibiltät aktivieren

# 6.2.4
- Noscript Bereich befindet sich nun im Twig Block "base_noscript"

# 6.2.3
- Reihenfolge der Daten nun wieder wie gehabt: erst generelle Tags, dann GA4 Daten
- doppeltes Purchase-Event bei aktiviertem Adwords Tracking deaktiviert

# 6.2.2
- Kompatibilitätsbugfix

# 6.2.1
- GA4 Events: Das Add To Cart Event auf der Listenseite heißt nun "add_to_cart_list". Bitte updaten Sie Ihre GTM Einstellungen.
- GA4 Events: Das Remove From Cart Event enthält nun ebenfalls den Namen des Produktes unter "item_name"

# 6.2.0
- Native Unterstützung von GA4


    ACHTUNG: Dies ist ein Major Update des Plugins für die Kompatibilität zu Google Analytics 4. Google wird zum 01.07.2023 das alte
    Universal Analytics (UA) abschalten. Mit dem Update auf diese Version wird das alte Enhanced Ecommerce deaktiviert (wir haben eine
    Funktion im Plugin hinterlegt, mit der Sie die alte Struktur noch eine Weile zurückholen können) und die neue GA4 Struktur wird
    aktiviert. Bitte prüfen Sie nach dem Update auf diese Plugin-Version, ob weiterhin alle Daten wie gewünscht in Google Analytics
    erscheinen. Weitere Informationen finden Sie zeitnah hier: https://www.codiverse.de/category/blog/

    Folgende Events werden von dieser Plugin Version unterstützt:

    - view_item_list
    - view_item
    - view_cart
    - begin_checkout
    - select_item
    - add_to_cart
    - remove_from_cart
    - purchase
    - confirm_order (Custom Event für die Bestellprüfungs-Seite)
    - add_payment_info

    Folgende Events wurden entfernt:

    - shopwareGTM.orderCompleted
    - gtmAddToCart
    - gtmRemoveFromCart

# 6.1.49
- kritischer Sicherheitsbugfix: die Option, eigene GET Parameter an den GTM zu übergeben, wurde vollständig entfernt, da hier eine Sicherheitslücke bestand. Bitte aktualisieren Sie das Plugin zeitnah.

# 6.1.48
- Neu: Config-Option "Container Code entfernen"

# 6.1.47
- Neu: Config-Option "GTM Code verzögert einfügen"

# 6.1.46
- SW6.5 Kompatibilität

# 6.1.45
- Custom JS URLs können nun auch einen eigenen Dateinamen haben
- Datalayer enthält nun die Herstellernummer auf der Detailseite

# 6.1.44
- Neuer Wert für Enhanced Conversions im Checkout: transactionCountryIso (ISO 3166-1 ALPHA-2)
- Neuer Wert für Enhanced Conversions im Checkout: transactionStateName (sofern verfügbar)
- Anpassung: aw_feed_country im Adwords Tag enthält nun den 2stelligen Country Code
- Anpassung: aw_feed_language im Adwords Tag enthält nun den 2stelligen Language Code

# 6.1.43
- Preis und Menge im Addtocart Event sind nun numerische Werte, keine Strings
- Addtocart und Removefromcart werden nun unabhängig vom Cookie Consent gefeuert, um die Kompatiblität mit Drittanbieter Systemen zu erhöhen. Bitte überprüfen Sie Ihre Einstellungen in GTM
- Kleine Anpassung für den UserCentrics Code

# 6.1.42
- Optimierungen für Add to Cart auf Listenseiten

# 6.1.41
- Backend-Beschreibung (Plugin-Settings) optimiert

# 6.1.40
- Neu: eigene URL für GTM.js angeben. Mehr dazu siehe hier: https://www.demirjasarevic.com/gtm-js-skript-eigener-server/
- mehr Optionen für den Einsatz von Enhanced Conversions - bitte Plugin Einstellungen prüfen!

# 6.1.39
- Anpassungen für die Kompatibilität mit dem Custom Products Plugin

# 6.1.38
- GTM Basis Code enthält jetzt die volle HTTPS URL

# 6.1.37
- Anpassungen am noscript-Tag

# 6.1.36
- SW6.4.10.0 Kompatibilität

# 6.1.35
- neue Plugin Option: Maximale Anzahl an Produkten im Impressions-Array

# 6.1.34
- Neu: auf der Finish Seite wird nun auch die Kund*innen-Mailadresse im Datalayer (Key: transactionEmail) ausgegeben

# 6.1.33
- Bugfix für mögliche Fehler in Neu-Installationen
- Anpassungen am noscript-Template

# 6.1.32
- Bugfix für 404-Seiten
- Bugfix für Custom Products

# 6.1.31
- Bugfix für Account-Seiten und Checkout in Verbindung mit Remarketing

# 6.1.30
- ecomm_pagetype 'home' wird nun auf der Startseite ausgegeben
- Basis Tag Manager Code wird nun auch auf Nicht-Standard Shopseiten ausgegeben

# 6.1.29
- Anpassungen für die Kompatibilität mit dem Custom Products Plugin

# 6.1.28
- Anpassung für Steuer-Berechnung im Checkout

# 6.1.27
- Kategorie IDs werden im Datalayer nun auf Listing- und Detailseiten ausgegeben

# 6.1.26
- neue Plugin Option: UserCentrics Kompatibiltät aktivieren
- Ausgabe des GTM Codes auf Landingpages

# 6.1.25
- Checkout exception handling

# 6.1.24
- Anpassung für Steuer-Berechnung im Checkout

# 6.1.23
- Checkout exception handling

# 6.1.22
- Produkt Kategorie ist immer die SEO Kategorie

# 6.1.21
- Remove From Cart Event wird nun auch aus dem Off-Canvas-WK gefeuert

# 6.1.20
- Hersteller Name nun in EE Produkten enthalten
- Gutschein-Code wird im EE Code auf der Bestellabschluss-Seite ausgegeben

# 6.1.19
- Bugfix für Sales Channel, die keine ID enthalten

# 6.1.18
- Bugfix für PHP8 > 8.0.2

# 6.1.17
- SW6.4.0.0 Compatibility

# 6.1.16
- Bugfixes in Datalayer Service

# 6.1.15
- Bugfixes für Pagehiding

# 6.1.15
- Bugfixes für Gutscheineingabe im Checkout

# 6.1.14
- Bugfixes

# 6.1.13
- Bugfixes für Remarketing Code

# 6.1.12
- AddtoCart und RemoveFromCart nutzen nun die SKU
- kleinere Bugfixes

# 6.1.11
- Bugfix für SKU in EE Checkout Daten
- Bugfix für Steuern im Checkout
- Katogorienamen jetzt optional im EE Checkout verfügbar

# 6.1.10
- GTM Code auch auf Newsletter Register-/Subscribe-Seiten
- Bugfix für Nettopreise in Ländern ohne Steuer

# 6.1.9
- Bugfix für Bestellungen mit Gutscheinen

# 6.1.8
- Ausgabe des GTM Containers nun auch auf Fehlerseiten

# 6.1.7
- GA Cookies werden nun bei der Anpassung der Cookie Präferenzen entfernt

# 6.1.6
- Anpassungen bei der View Einbindung in Checkout/Confirm

# 6.1.5
- Bugfix für Versandarten
- Bugfix für leere Kategorie-Zuordnungen

# 6.1.4
- Bugfix für steuerfreie Länder
- Bugfix für Listenseite ohne Layoutzuweisung

# 6.1.3
- Bugfix für Einkaufswelten

# 6.1.2
- Listenseiten Performance Update

# 6.1.1
- Kompatibilitäts-Fix für Drittanbieter Plugins

# 6.1.0
- Enhanced Ecommerce Tracking JS Events (Addtocart & Removefromcart)
- Adwords Service integriert

# 6.0.5
- Bugfix für Detailseiten
- Bugfix für EE

# 6.0.4
- Bugfix für Multishops

# 6.0.3
- SW6.2.x Kompatibilität

# 6.0.2
- Enhanced Ecommerce Parameter eingebunden

# 6.0.1
- Remarketing Parameter eingebunden

# 6.0.0
- Erste Veröffentlichung
