<?php
// Register all Dutch strings from Maison & Opus theme

// Define all strings to be registered
$strings = array(
    // Layout/Navigation
    "Diensten", "Zoekopdracht", "Over ons", "Contact", "Online & op afspraak",
    "Horloges gekocht · verkocht · onderhouden", "Primary", "Utility", "Winkelwagen",

    // Footer
    "Een klein onafhankelijk horlogehuis.", "Horloges gekocht, verkocht en met de hand verzorgd.",
    "Collectie", "Alle horloges", "De database", "Inloggen", "Onderhoud", "Onze diensten",
    "Onderhoud aanvragen", "Restauratie", "Neem contact op", "De Entree 201, Amsterdam",
    "Onafhankelijk horlogehuis", "Colofon", "Privacy", "Voorwaarden",

    // Homepage hero & tiles
    "Een chique gouden horloge met groene wijzerplaat", "Horloges —", "gekocht, verkocht en",
    "met de hand hersteld.",
    "Opus biedt een zorgvuldig samengestelde selectie bijzondere horloges, en volledig onderhoud en restauratie in eigen atelier.",
    "Bekijk de collectie", "Bijzondere horloges,", "op voorraad.", "Bekijk alles →",
    "Wij kopen ook", "uw horloge.", "Vraag een taxatie aan →", "Onderhoud &", "restauratie.",
    "Nu op voorraad", "Bekijk alle horloges →", "De collectie wordt voorbereid.", "Shop op merk",

    // Service section
    "Breng uw horloge", "naar de werkbank.",
    "Een groot deel van ons werk is de zorg voor horloges — die van ons en van andere merken, vintage en modern. Junior, onze horlogemaker, onderzoekt uw horloge en brengt een offerte uit voordat er iets gebeurt.",
    "Wanneer wij een horloge innemen, ontvangt u op uw telefoon een innamepagina: foto's, een omschrijving van het werk en onze voorwaarden. U leest het en gaat akkoord — en uw akkoord wordt vastgelegd voordat het werk begint.",
    "Bekijk onze diensten", "Volledige revisie", "op offerte", "Polijsten & afwerken",
    "Glas & afdichting", "10 dagen", "Bandje plaatsen", "dezelfde dag", "Batterij & afdichting",

    // Contact page
    "Een vraag over een horloge, onderhoud of een afspraak in onze boutique? Stuur ons een bericht — we reageren doorgaans binnen één werkdag.",
    "Dank u — uw bericht is ontvangen. We nemen zo snel mogelijk contact met u op.",
    "Er ontbrak iets. Controleer uw naam, een geldig e-mailadres en uw bericht.",
    "Naam", "E-mailadres", "Onderwerp", "bijv. afspraak, onderhoud, een specifiek horloge",
    "Bericht", "Waarmee kunnen we u helpen?", "Verstuur bericht",
    "Uw gegevens gebruiken we uitsluitend om uw vraag te behandelen.",
    "Bezoek", "Amsterdam", "Uitsluitend op afspraak", "Bereikbaar", "Openingstijden",
    "Bedrijfsgegevens", "KvK", "Vestigingsnr.",
    "Eenmanszaak · In- en verkoop en reparatie van uurwerken",
    "Locatie Opus Watches — De Entree 201, Amsterdam",

    // Shop filters & mega-menu
    "Merken", "Nieuw binnen", "< € 5.000", "€ 5.000 – € 10.000",
    "€ 10.000 – € 25.000", "€ 25.000 – € 100.000",

    // Index
    "Hier staat nog niets.",

    // Watch database
    "Gelimiteerd",

    // Product single
    "Authenticiteit gegarandeerd", "Onderhoud & revisie in eigen atelier",
    "Specificaties", "Over dit horloge",
);

// Register unique strings
$unique = array_unique($strings);
$registered = 0;
foreach ($unique as $str) {
    $id = icl_register_string("maison-opus", md5($str), $str);
    if ($id) {
        $registered++;
    }
}

echo "Registered $registered strings for maison-opus theme\n";
?>
