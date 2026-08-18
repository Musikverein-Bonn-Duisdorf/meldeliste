<?php
/**
 * Default Leih-/Rückgabe-Vertragstexte (config table, Type text).
 * Leerzeile = neuer nummerierter Absatz. Zeilen mit "- " werden zur Unterliste.
 * Platzhalter: {org} {start} {duration} {end} {returnDue} {borrower} {item} {fee} {kaution}
 * {rep} {repPhrase} {invNr} {invNrPhrase}
 * Absätze mit {fee} bzw. {kaution} nur wenn Betrag &gt; 0 €.
 */
function getLoanClauseDefaults() {
    $d = function ($param, $value, $description) {
        return array(
            'Parameter' => $param,
            'Value' => $value,
            'Type' => 'text',
            'Description' => $description,
        );
    };
    return array(
        $d(
            'loanText',
            <<<'TXT'
Die Leihe beginnt am {start} und ist {duration}.

Das Eigentum an der Leihsache verbleibt bei {org}. Der Entleiher erwirbt kein Eigentum und kein Pfandrecht an der Leihsache.

Der Entleiher verpflichtet sich, die Leihsache sorgfältig zu behandeln, nur bestimmungsgemäß zu nutzen und sie vor Verlust, Diebstahl und Beschädigung zu schützen. Verlust, Diebstahl oder wesentliche Schäden sind {org} unverzüglich anzuzeigen; der Entleiher haftet hierfür nach den allgemeinen gesetzlichen Regeln.

Der aktuelle Zustand der Leihsache bei Ausgabe wird im Anhang „Zusätzliche Vereinbarungen“ zu diesem Vertrag protokolliert.

Der Entleiher trägt gemäß § 601 Abs. 1 BGB die gewöhnlichen Kosten der Erhaltung der Sache. Dies umfasst insbesondere, aber nicht ausschließlich:
- die Reinigung und Wartung der Leihsache
- Verbrauchsmaterial, etwa Öle, Fette, Blätter, Reinigungsmittel
- kleinere Reparaturen gewöhnlicher Verschleißschäden, die jährliche Kosten von insgesamt 100 Euro nicht überschreiten

Die Verpfändung oder der Verkauf der Leihsache ist untersagt. Die vorübergehende Übergabe an andere Mitglieder von {org} zu Proben, Auftritten und vergleichbaren Vereinszwecken ist zulässig. Eine sonstige Weitergabe an Dritte bedarf der Zustimmung des Vorstandes.

Der Verleiher kann die Leihsache jederzeit, insbesondere aus wichtigem Grund, zurückfordern. Ein wichtiger Grund liegt insbesondere im Fall der Beendigung der Vereinsmitgliedschaft des Entleihers gemäß den Bestimmungen der Satzung von {org} vor. Das gesetzliche Kündigungsrecht gemäß § 605 BGB und § 604 Abs. 3 BGB bleibt unberührt.

Die Leihsache ist {returnDue} vollständig und in einem dem Alter und der üblichen Abnutzung entsprechenden Zustand an den Vorstand von {org} zurückzugeben. Die Rückgabe wird gesondert protokolliert.

Abweichend von § 606 BGB verjähren Ansprüche des Verleihers wegen Veränderung oder Verschlechterung der Leihsache in sechs Monaten ab dem Zeitpunkt, in dem der Verleiher von den anspruchsbegründenden Umständen Kenntnis erlangt oder ohne grobe Fahrlässigkeit erlangen müsste, spätestens jedoch mit Ablauf von drei Jahren nach Rückgabe der Leihsache.

Änderungen und Ergänzungen dieses Vertrages bedürfen zu ihrer Wirksamkeit der Textform. Dies gilt auch für eine Änderung oder Aufhebung dieses Textformerfordernisses selbst.

Sollte eine Bestimmung dieses Vertrages unwirksam oder undurchführbar sein oder werden, bleibt die Wirksamkeit der übrigen Bestimmungen hiervon unberührt. An ihre Stelle tritt die jeweilige gesetzliche Regelung, die dem von den Parteien wirtschaftlich Gewollten am nächsten kommt.

Für die Überlassung erhebt {org} eine Leihgebühr in Höhe von {fee}. Die Leihgebühr ist mit Vertragsschluss fällig und wird nicht erstattet.

Für die Dauer der Leihe hinterlegt der Entleiher eine Kaution in Höhe von {kaution}. Die Kaution wird bei ordnungsgemäßer Rückgabe zurückgezahlt; berechtigte Abzüge wegen Beschädigung, Verlust oder fehlender Bestandteile sind zulässig.
TXT
            ,
            'Leihvertrag. Leerzeile = Absatz. {org} {start} {duration} {returnDue} {fee} {kaution}'
        ),
        $d(
            'loanReturnText',
            <<<'TXT'
Mit Unterzeichnung bestätigen {org}{repPhrase}, und {borrower}, dass die nachstehend bezeichnete Leihsache ({item}{invNrPhrase}, entliehen am {start}) zurückgegeben wurde.

Die Leihsache wurde auf Vollständigkeit und offensichtlich erkennbare Schäden und Mängel geprüft. Offensichtliche Schäden und Mängel werden im Anhang „Zusätzliche Vereinbarungen“ vermerkt; andernfalls gilt der Zustand der Leihsache als dem vertragsgemäßen Verbrauch entsprechend.

Für den Fall, dass das Leihverhältnis noch nicht aus einem anderen Grund erloschen ist, endet es mit der Rückgabe dieses Inventarstücks.

Die hinterlegte Kaution in Höhe von {kaution} wird mit dieser Rückgabe an {borrower} ausgezahlt. Berechtigte Abzüge wegen Beschädigung, Verlust oder fehlender Bestandteile werden in diesem Protokoll vermerkt.
TXT
            ,
            'Rückgabeprotokoll. Leerzeile = Absatz. {org} {borrower} {item} {start} {kaution} {repPhrase} {invNrPhrase}'
        ),
    );
}
