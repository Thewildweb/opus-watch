<?php
/**
 * Seed the service catalogue from the client's pricing doc
 * (horloge_services_prijslijst_aangepast.docx). Idempotent: updates by slug.
 */

$term = get_term_by( 'slug', 'service', 'product_cat' );
if ( ! $term ) {
	WP_CLI::error( 'service category not found' );
}

wp_update_term(
	$term->term_id,
	'product_cat',
	array(
		'description' =>
			"Opus Watches biedt een breed aanbod aan diensten voor het onderhoud van uw horloge. Onze ervaren en gecertificeerde horlogemakers behandelen elk horloge met de zorg en precisie die het verdient — van volledig onderhoud tot verschillende deelreparaties.\n\n" .
			"In ons atelier zijn wij gespecialiseerd in het onderhoud van Rolex, maar wij richten ons ook op andere luxe merken zoals Omega, Breitling, Cartier en TAG Heuer. Benieuwd of wij u kunnen helpen met het onderhoud van uw horloge? Neem gerust contact met ons op.",
	)
);

$services = array(

	// ── Volledig onderhoud ────────────────────────────────────────────
	array(
		'slug'  => 'revisie',
		'name'  => 'Revisie',
		'group' => 'onderhoud',
		'order' => 10,
		'price' => 'Vanaf €450',
		'short' => 'Volledige demontage, reiniging, smering en afstelling van het uurwerk — afgeleverd met twee jaar garantie op het functioneren.',
		'desc'  =>
			"Een volledige revisie van uw luxe horloge is essentieel voor het juist functioneren, de precisie en de waarde van uw horloge. Met periodiek onderhoud voorkomt u onnodig hoge kosten bij een reparatie als uw horloge niet meer werkt zoals het zou moeten. Ons volledig onderhoud omvat een complete demontage van het uurwerk, waarbij elk onderdeel zorgvuldig wordt gereinigd en geïnspecteerd. Tijdens de revisie vervangen wij versleten componenten en smeren wij alle bewegende delen met hoogwaardige Zwitserse smeermiddelen. Na de revisie wordt het uurwerk opnieuw geassembleerd en nauwkeurig afgesteld volgens fabrieksspecificaties. Elke revisie wordt afgesloten met een waterdichtheidstest en een uitgebreide kwaliteitscontrole.\n\n" .
			"Onze gediplomeerde horlogemakers hebben jarenlange ervaring en beschikken over gespecialiseerde trainingen en gereedschap voor het onderhoud van zelfs de meest complexe mechanische uurwerken. Bijzondere aandacht gaat uit naar de gangregulatie, waarbij het horloge in verschillende posities wordt afgesteld om optimale nauwkeurigheid te garanderen. Wij testen de amplitude, beat error en tijdsvariaties om ervoor te zorgen dat uw horloge weer optimaal functioneert.\n\n" .
			"Als u kiest voor een volledige revisie wordt uw horloge netjes afgeleverd met twee jaar garantie op het functioneren van het uurwerk. De kosten voor een revisie van een standaard automatisch uurwerk beginnen bij €450; de exacte prijs hangt af van het merk, het model en de complexiteit van uw horloge. Deze prijs omvat de onderdelen die intensief worden gebruikt en die bij iedere servicebeurt vervangen horen te worden, zoals de opwindveer en de rotoras. In sommige gevallen is het nodig om meer onderdelen te vervangen; hiervoor kunnen kosten bijkomen. Daarom stellen wij altijd eerst geheel vrijblijvend een prijsopgave op en beginnen we pas aan de reparatie nadat u hiermee akkoord bent gegaan.",
	),
	array(
		'slug'  => 'revisie-chronograaf',
		'name'  => 'Revisie chronograaf',
		'group' => 'onderhoud',
		'order' => 20,
		'price' => 'Vanaf €650',
		'short' => 'Gespecialiseerde revisie voor chronografen zoals de Rolex Daytona en Omega Speedmaster — twee jaar garantie op uurwerk én chronograafmechanisme.',
		'desc'  =>
			"De chronograafrevisie is een gespecialiseerde vorm van horlogeservice die extra expertise vereist vanwege de complexiteit van het mechanisme. Een chronograaf bevat aanzienlijk meer onderdelen dan een standaard uurwerk, wat de reparatie tijdsintensiever en technisch veeleisender maakt. Onze ervaren horlogemakers hebben jarenlange ervaring met de Rolex Daytona, de Omega Speedmaster en andere iconische chronografen.\n\n" .
			"Tijdens de revisie wordt het complete uurwerk gedemonteerd, inclusief het stopwatchmechanisme met al zijn schakels, hefbomen en veren. Elk onderdeel wordt ultrasoon gereinigd en onder een microscoop geïnspecteerd op slijtage. Versleten onderdelen worden vervangen door hoogwaardige onderdelen van Zwitserse of Duitse makelij. Het chronograafmechanisme vereist bijzondere aandacht bij de smering, aangezien elke component precies de juiste hoeveelheid en het juiste type olie moet ontvangen. Na assemblage wordt de chronograaffunctie uitgebreid getest en afgesteld.\n\n" .
			"De kosten van een chronograafrevisie beginnen bij €650, inclusief de gangbare serviceonderdelen. Na de revisie bent u weer verzekerd van de hoge prestatienormen waarvoor uw chronograaf is gemaakt, met twee jaar garantie op het functioneren van het uurwerk en het chronograafmechanisme. Indien er meer onderdelen vervangen moeten worden, maken wij altijd eerst een vrijblijvende prijsopgave en beginnen we pas na uw akkoord.",
	),
	array(
		'slug'  => 'revisie-vintage',
		'name'  => 'Revisie vintage',
		'group' => 'onderhoud',
		'order' => 30,
		'price' => 'Vanaf €550',
		'short' => 'Revisie met respect voor originaliteit, patina en verzamelwaarde; originele onderdelen worden waar mogelijk behouden of gesourced.',
		'desc'  =>
			"Vintage horloges vertellen een verhaal — en dat verhaal wordt ook verteld door het uurwerk. Daarom vraagt een vintage revisie om bijzondere aandacht en vakmanschap. Oudere horloges, vooral klassieke modellen van Rolex, Omega en Cartier uit de jaren 60, 70 en 80, hebben vaak te maken met materiaalveroudering die een gespecialiseerde aanpak vereist.\n\n" .
			"Onze revisie voor vintage horloges begint met een grondige analyse van de staat van het uurwerk en alle componenten. Oude oliën en smeermiddelen zijn vaak uitgedroogd of verhard, wat kan leiden tot verhoogde wrijving en slijtage. Bij de restauratie gebruiken wij aangepaste reinigingstechnieken die delicate onderdelen beschermen tegen beschadiging. Originele onderdelen worden waar mogelijk behouden om de authenticiteit en verzamelwaarde te waarborgen; onze horlogemakers begrijpen dat elke wijziging invloed kan hebben op de waarde van een vintage horloge.\n\n" .
			"Indien vervanging noodzakelijk is, zoeken wij eerst naar originele vintage onderdelen via ons wereldwijde netwerk van gespecialiseerde leveranciers. Als laatste optie kunnen wij onderdelen op maat fabriceren, waarbij wij de originele specificaties nauwgezet volgen. Wij respecteren patina en tekenen van leeftijd waar deze horloges hun karakter aan ontlenen, terwijl we de mechanische integriteit volledig herstellen. De prijs van een vintage revisie begint bij €550.",
	),
	array(
		'slug'  => 'revisie-vintage-chronograaf',
		'name'  => 'Revisie vintage chronograaf',
		'group' => 'onderhoud',
		'order' => 40,
		'price' => 'Vanaf €800',
		'short' => 'Onze meest specialistische dienst: vintage chronografen zoals de Rolex "Zenith" Daytona en de Omega Speedmaster Professional.',
		'desc'  =>
			"De complexiteit van een chronograaf gecombineerd met de uitdagingen van een vintage horloge maakt deze service tot een van onze meest specialistische diensten. Vintage chronografen zoals de Rolex \"Zenith\" Daytona uit de jaren 80 en 90, of de Omega Speedmaster Professional, zijn niet alleen mechanische meesterwerken maar ook belangrijke verzamelobjecten met een rijke geschiedenis.\n\n" .
			"Een vintage chronograafrevisie vereist diepgaande kennis van historische uurwerken en authentieke restauratietechnieken. Tijdens de revisie wordt elk onderdeel van het chronograafmechanisme zorgvuldig geïnspecteerd onder vergroting. Historische componenten die niet meer leverbaar zijn, kunnen in sommige gevallen door ons worden gefabriceerd met traditionele instrumenten en authentieke materialen. De afstelling van het uurwerk en het chronograafmechanisme gebeurt met grote nauwkeurigheid en respect voor de originele fabrieksspecificaties uit die tijd, waarbij wij gebruikmaken van originele documentatie en technische handleidingen.\n\n" .
			"Speciale aandacht gaat uit naar het behoud van originele wijzerplaten, indexen en wijzers, omdat deze cruciaal zijn voor de waarde en authenticiteit. Tritium-lichtmassa op vintage wijzerplaten wordt met zorg behandeld volgens veiligheidsvoorschriften. Deze uitgebreide service start vanaf €800, afhankelijk van de complexiteit en staat van het uurwerk. Voor zeer zeldzame modellen maken wij een individuele offerte op basis van een grondige inspectie en research naar het specifieke model.",
	),
	array(
		'slug'  => 'revisie-jaren-50-en-ouder',
		'name'  => "Revisie jaren '50 en ouder",
		'group' => 'onderhoud',
		'order' => 50,
		'price' => 'Op aanvraag',
		'short' => "Horloges uit de jaren '50 en ouder zijn stuk voor stuk uniek; wij bepalen de prijs na een uitgebreide inspectie en research.",
		'desc'  =>
			"Horloges uit de jaren 50 en ouder vertegenwoordigen een gouden tijdperk in de horlogegeschiedenis en vereisen de meest gespecialiseerde restauratie. Deze horloges, vaak van prestigieuze merken zoals Rolex, Omega of Cartier, zijn zeldzame verzamelobjecten met bijzondere historische en emotionele waarde. De revisie van deze klassiekers vraagt om uitzonderlijke expertise vanwege de leeftijd van de materialen en de vaak unieke constructie: veel onderdelen zijn niet meer leverbaar via reguliere kanalen en moeten op maat worden gemaakt met traditionele technieken en authentieke materialen.\n\n" .
			"De staat van elk horloge uit deze periode is uniek, wat een individuele beoordeling noodzakelijk maakt voordat wij aan het werk gaan. Onze service begint altijd met een grondige inspectie waarbij wij de originaliteit van alle componenten verifiëren aan de hand van historische documentatie en ons uitgebreide archief. Bij de restauratie wordt extreme voorzichtigheid betracht om het authentieke karakter te bewaren zonder de functionaliteit te compromitteren. Componenten met gevaarlijke stoffen zoals radium vereisen speciale behandeling volgens moderne veiligheidsvoorschriften.\n\n" .
			"Vanwege de unieke aard van elk horloge uit deze periode stellen wij de prijs op aanvraag vast, na een uitgebreide inspectie en research. Neem contact op met onze horlogemakers voor een vrijblijvende beoordeling van uw horloge uit dit bijzondere tijdperk.",
	),

	// ── Deelreparaties ────────────────────────────────────────────────
	array(
		'slug'  => 'polijsten',
		'name'  => 'Polijsten',
		'group' => 'deelreparatie',
		'order' => 60,
		'price' => 'Vanaf €150',
		'short' => 'Professioneel polijsten volgens de richtlijnen van de fabrikant; het horloge wordt volledig gedemonteerd en elk onderdeel afzonderlijk gepolijst.',
		'desc'  =>
			"Ieder horloge dat wordt gedragen loopt op den duur krassen op. Volgens sommigen geeft dit een horloge karakter, maar voor anderen is het een afleiding van de schoonheid van hun horloge. Opus Watches kan uw horloge op professionele wijze polijsten, zodat krassen verdwijnen of minder zichtbaar worden en uw horloge in veel gevallen weer als nieuw oogt.\n\n" .
			"Bij het polijsten hanteren wij de richtlijnen en technische eisen van de fabrikant van uw horloge. Zo behoudt uw horloge de juiste afwerking, of brengen wij deze terug waar die verloren is gegaan. Om dit te garanderen demonteren wij ieder horloge volledig en polijsten wij elk onderdeel afzonderlijk. Indien nodig vervangen we de pakkingen van uw horloge, zodat u ook na een polijstbeurt verzekerd bent van de waterdichtheid.\n\n" .
			"De kosten voor het polijsten in combinatie met een revisie zijn €150 voor standaardmodellen en €200 voor sportmodellen. Als losstaande behandeling kost het polijsten €200 voor standaardmodellen en €250 voor sportmodellen.",
	),
	array(
		'slug'  => 'horlogeglas-vervangen',
		'name'  => 'Horlogeglas vervangen',
		'group' => 'deelreparatie',
		'order' => 70,
		'price' => 'Vanaf €60',
		'short' => 'Plexiglas of saffierglas, inclusief nieuwe afdichting en waterdichtheidstest.',
		'desc'  =>
			"Het vervangen van een beschadigd horlogeglas is een impactvolle ingreep die direct invloed heeft op zowel de bescherming als het uiterlijk van uw horloge. Een beschadigd glas doet afbreuk aan de charme van uw horloge en kan zelfs een risico vormen voor de waterdichtheid.\n\n" .
			"Wij werken met beide typen horlogeglas: klassiek plexiglas en modern saffierglas. Plexiglas werd veel gebruikt in vintage horloges en heeft de charme van authenticiteit, maar is gevoelig voor krassen. Saffierglas is tegenwoordig de standaard in luxehorloges zoals die van Rolex, Omega en Cartier, en biedt superieure krasbestendigheid en helderheid. Wij werken uitsluitend met glazen van de hoogste kwaliteit; na het plaatsen controleren wij altijd de waterdichtheid en zorgen we dat alles keurig uitgelijnd staat. Voor vintage restauraties gebruiken wij waar mogelijk authentiek plexiglas om de originele uitstraling te behouden.\n\n" .
			"In combinatie met een revisie rekenen wij voor het vervangen van een horlogeglas €60 voor plexiglas en €95 voor saffierglas. Als losse service kost het €95 voor plexiglas en €125 voor saffierglas. Beide prijzen omvatten installatie, een nieuwe afdichting en een waterdichtheidstest.",
	),
	array(
		'slug'  => 'kastpoten-opmaken',
		'name'  => 'Kastpoten opmaken',
		'group' => 'deelreparatie',
		'order' => 80,
		'price' => '€100',
		'short' => 'Herstel van de vorm en afwerking van de kastpoten (lugs) naar fabrieksspecificaties.',
		'desc'  =>
			"De kastpoten, ook wel lugs genoemd, zijn een belangrijk onderdeel van het uiterlijk van uw horloge. Deze verbindingspunten tussen kast en band zijn gevoelig voor slijtage en krassen door bandwisselingen en dagelijks gebruik. Onze gespecialiseerde behandeling herstelt de oorspronkelijke vorm en afwerking — vooral belangrijk bij merken zoals Rolex, waar de karakteristieke vorm van de kastpoten bepalend is voor het ontwerp.\n\n" .
			"Het proces vereist precisiewerk van een ervaren horlogemaker die exact weet hoeveel materiaal verwijderd kan worden zonder de structurele integriteit aan te tasten. Bij vintage horloges is het behoud van de oorspronkelijke kastafmetingen cruciaal voor de verzamelwaarde. Wij gebruiken gespecialiseerd gereedschap om de kastpoten terug te brengen naar hun originele vorm en afwerking; gepolijste, geborstelde of gecombineerde oppervlaktebehandelingen worden nauwkeurig gereproduceerd.\n\n" .
			"Voor €100 herstellen wij de kastpoten van uw horloge naar fabrieksspecificaties. Het resultaat is een horloge waarvan de kastpoten er weer onberispelijk uitzien, alsof het net uit de fabriek komt.",
	),
	array(
		'slug'  => 'wijzerplaat-wisselen',
		'name'  => 'Wijzerplaat wisselen',
		'group' => 'deelreparatie',
		'order' => 90,
		'price' => 'Vanaf €75',
		'short' => 'Vervanging van een beschadigde of verkleurde wijzerplaat — of een compleet nieuwe uitstraling; bij een revisie kosteloos.',
		'desc'  =>
			"Veel liefhebbers willen de wijzerplaat van hun horloge kunnen vervangen — omdat deze beschadigd is geraakt of verkleurd, of gewoon om hun vertrouwde horloge een compleet nieuwe uitstraling te geven. Onze horlogemakers helpen u hier graag bij. Beschikt u niet over een andere wijzerplaat? Dan kan Opus Watches deze voor u sourcen.\n\n" .
			"De kosten voor het vervangen van een standaard wijzerplaat zijn €75, en bij een chronograafhorloge €100. Laat u de wijzerplaat vervangen tijdens de revisie van uw horloge? Dan is dit geheel kosteloos.",
	),
	array(
		'slug'  => 'rolex-band-restauratie',
		'name'  => 'Rolex band restauratie',
		'group' => 'deelreparatie',
		'order' => 100,
		'price' => 'Vanaf €300',
		'short' => 'Volledige restauratie van jubilee- en oysterbanden: staal en bicolor vanaf €300, goud op aanvraag.',
		'desc'  =>
			"De band van een Rolex is misschien wel even belangrijk als de kast van het horloge. Het design maakt het horloge herkenbaar, en de doorgaans superieure technische kenmerken zorgen dat u het horloge comfortabel kunt dragen en dat het veilig om uw pols zit.\n\n" .
			"Door de hoge kwaliteit van Rolex-banden gaan deze bij juist gebruik vaak tientallen jaren mee, maar over de jaren kunnen de pennen die de schakels verbinden slijtage oplopen. Het gevolg is dat er rek in de band optreedt en deze zijn strakke vorm verliest — in het uiterste geval kunnen pennen zelfs breken, waardoor u uw horloge kunt verliezen.\n\n" .
			"Onze horlogemakers herstellen dit met een volledige bandrestauratie. De band wordt volledig gedemonteerd en de schakels worden stuk voor stuk geïnspecteerd. Versleten bandpennen worden vervangen, evenals schakels die niet meer bruikbaar zijn — waarbij de authenticiteit van uw horloge altijd streng in acht wordt genomen. Ook pennen van fliplocks en safety catches kunnen worden vervangen, zodat uw toolwatch veilig om uw pols blijft zitten. De restauratie voeren wij uit aan zowel jubilee- als oysterbanden, in staal en in goud, met traditioneel en gespecialiseerd gereedschap — zodat uw band in veel gevallen weer zo goed als nieuw wordt.\n\n" .
			"De kosten voor een bandrestauratie beginnen bij €300 voor stalen en bicolor banden. De restauratie van een gouden band is altijd op aanvraag en volgt na een inspectie van de band: de kosten kunnen sterk variëren door het benodigde materiaal en de mate van slijtage. Neem gerust geheel vrijblijvend contact met ons op.",
	),
);

// Old placeholder products to fold into the new set (old slug => new slug).
$renames = array(
	'full-revision'      => 'revisie',
	'polishing-refinish' => 'polijsten',
	'crystal-gasket'     => 'horlogeglas-vervangen',
);

$existing = array();
foreach ( wc_get_products( array( 'status' => 'any', 'limit' => -1 ) ) as $p ) {
	$existing[ $p->get_slug() ] = $p->get_id();
}

foreach ( $services as $s ) {
	$id = 0;
	if ( isset( $existing[ $s['slug'] ] ) ) {
		$id = $existing[ $s['slug'] ];
	} else {
		foreach ( $renames as $old => $new ) {
			if ( $new === $s['slug'] && isset( $existing[ $old ] ) ) {
				$id = $existing[ $old ];
				break;
			}
		}
	}

	$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
	$product->set_name( $s['name'] );
	$product->set_slug( $s['slug'] );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_short_description( $s['short'] );
	$product->set_description( $s['desc'] );
	$product->set_menu_order( $s['order'] );
	$product->set_category_ids( array( (int) get_term_by( 'slug', 'service', 'product_cat' )->term_id ) );
	$product->set_virtual( true );
	$product->update_meta_data( '_opus_price_display', $s['price'] );
	$product->update_meta_data( '_opus_service_group', $s['group'] );
	$pid = $product->save();
	WP_CLI::log( ( $id ? 'updated' : 'created' ) . " {$pid}\t{$s['name']}\t{$s['price']}" );
}

WP_CLI::success( 'Service catalogue seeded.' );
