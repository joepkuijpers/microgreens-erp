<?php
$assetPrefix = '../';
$navPrefix = '../';

include '../includes/layout_start.php';
?>

<style>
.settings-page {
    padding: 30px;
}

.settings-header {
    margin-bottom: 30px;
}

.settings-header h1 {
    margin: 0;
    font-size: 32px;
    color: #1e3a8a;
}

.settings-header p {
    margin-top: 8px;
    color: #6b7280;
    font-size: 16px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
}

.settings-card {
    background: #ffffff;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
    border-left: 6px solid #0f766e;
    text-decoration: none;
    color: inherit;
    transition: 0.2s ease;
}

.settings-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
}

.settings-card h2 {
    margin: 0 0 10px 0;
    font-size: 20px;
    color: #111827;
}

.settings-card p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.settings-card.general { border-left-color: #1e3a8a; }
.settings-card.sensors { border-left-color: #059669; }
.settings-card.climate { border-left-color: #ea580c; }
.settings-card.lighting { border-left-color: #f59e0b; }
.settings-card.water { border-left-color: #2563eb; }
.settings-card.notifications { border-left-color: #dc2626; }
.settings-card.pi { border-left-color: #7c3aed; }
.settings-card.database { border-left-color: #0f766e; }
.settings-card.backups { border-left-color: #16a34a; }
.settings-card.users { border-left-color: #6b7280; }
.settings-card.erp { border-left-color: #65a30d; }
.settings-card.haccp { border-left-color: #dc2626; }
.settings-card.knf { border-left-color: #059669; }
.settings-card.advanced { border-left-color: #111827; }

.settings-status {
    margin-top: 30px;
    padding: 18px 22px;
    background: #ecfdf5;
    border-left: 5px solid #16a34a;
    border-radius: 14px;
    color: #065f46;
    font-weight: 600;
}
</style>

<div class="settings-page">
    <div class="settings-header">
        <h1>Instellingen</h1>
        <p>Professioneel configuratiecentrum voor Microgreens ERP.</p>
    </div>

    <div class="settings-grid">
        <a class="settings-card general" href="../settings.php">
            <h2>Algemeen</h2>
            <p>Basisinstellingen zoals bedrijfsgegevens, standaardwaarden en huidige actieve systeeminstellingen.</p>
        </a>

        <a class="settings-card sensors" href="#">
            <h2>Sensoren</h2>
            <p>Configuratie voor BH1750, temperatuur, luchtvochtigheid en toekomstige sensoren.</p>
        </a>

        <a class="settings-card climate" href="#">
            <h2>Klimaat</h2>
            <p>Temperatuurgrenzen, luchtvochtigheid, ventilatie en automatische klimaatregeling.</p>
        </a>

        <a class="settings-card lighting" href="#">
            <h2>Verlichting</h2>
            <p>Luxgrenzen, lichtschema’s, dag/nacht-cycli en groeilampen.</p>
        </a>

        <a class="settings-card water" href="#">
            <h2>Water</h2>
            <p>Watergift, irrigatie, pompen, reservoirs en watercontrole.</p>
        </a>

        <a class="settings-card notifications" href="#">
            <h2>Meldingen</h2>
            <p>Waarschuwingen voor sensoren, voorraad, batches, onderhoud en fouten.</p>
        </a>

        <a class="settings-card pi" href="#">
            <h2>Raspberry Pi</h2>
            <p>CPU, geheugen, opslag, uptime, services en hardwarestatus.</p>
        </a>

        <a class="settings-card database" href="#">
            <h2>Database</h2>
            <p>SQLite-controle, onderhoud, tabellen, integriteit en optimalisatie.</p>
        </a>

        <a class="settings-card backups" href="#">
            <h2>Back-ups</h2>
            <p>Handmatige en automatische back-ups van database en projectbestanden.</p>
        </a>

        <a class="settings-card users" href="#">
            <h2>Gebruikers</h2>
            <p>Gebruikersbeheer, rollen, rechten en toekomstige loginfunctionaliteit.</p>
        </a>

        <a class="settings-card erp" href="#">
            <h2>ERP</h2>
            <p>Batchnummers, voorraadregels, verkoopinstellingen en traceability-logica.</p>
        </a>

        <a class="settings-card haccp" href="#">
            <h2>HACCP</h2>
            <p>Voedselveiligheid, controles, afwijkingen, schoonmaak en registraties.</p>
        </a>

        <a class="settings-card knf" href="#">
            <h2>KNF</h2>
            <p>Korean Natural Farming instellingen, recepten, schema’s en fermentaties.</p>
        </a>

        <a class="settings-card advanced" href="#">
            <h2>Geavanceerd</h2>
            <p>Technische instellingen voor ontwikkelaars, debugging en systeembeheer.</p>
        </a>
    </div>

    <div class="settings-status">
        Stap 1 actief: instellingenmodule is voorbereid als professionele meerpagina-structuur.
    </div>
</div>

<?php include '../includes/layout_end.php'; ?>
