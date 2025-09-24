# Il Giardino Segreto – Personalizzazioni WooCommerce

Questo repository contiene il plugin WordPress **IGS Ecommerce Customizations**, creato per raggruppare le personalizzazioni più richieste per l'ecommerce de Il Giardino Segreto. Il plugin permette di mantenere gli snippet in un unico punto, facilitandone l'attivazione e la manutenzione.

## Funzionalità incluse

- **Barra di avanzamento per la spedizione gratuita** con messaggi personalizzabili per carrello e checkout.
- **Badge sconto** che mostra automaticamente la percentuale di riduzione sui prodotti in promozione.
- **Badge "Novità"** per i prodotti pubblicati da poco con etichetta e durata configurabili.
- **Campi aggiuntivi in checkout** per Codice Fiscale, Partita IVA e messaggio regalo, completi di salvataggio e visualizzazione su email e area amministrativa.

Tutte le funzioni possono essere attivate o disattivate da una pagina impostazioni dedicata.

## Installazione

1. Scarica l'intero contenuto della cartella `igs-ecommerce-customizations` e comprimilo in un file `.zip`.
2. Carica l'archivio dal pannello **Plugin → Aggiungi nuovo** di WordPress e attivalo.
3. Vai in **WooCommerce → Personalizzazioni IGS** per configurare soglie, testi e preferenze.

## Sviluppo

- Il codice è organizzato in classi all'interno della cartella `includes/` per mantenere ogni funzionalità separata.
- Gli stili front-end si trovano in `assets/css/frontend.css`.
- Per aggiungere nuove personalizzazioni è sufficiente creare una nuova classe in `includes/` e inizializzarla nel bootstrap del plugin.

Per contribuire, apri una pull request con una descrizione dettagliata delle modifiche e ricordati di eseguire un controllo sintattico PHP (`php -l`) prima di inviare.
