<?php
include("./include/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['gerichtsname'] ?? '';
    $cuisine_id = ($_POST['cuisine'] ?? 0);
    $ernaehrungsweise_id = ($_POST['ernaehrungsweise'] ?? 0);
    $dauer = ($_POST['dauer'] ?? 0);
    $kalorien = ($_POST['kalorien'] ?? 0);
    $beschreibung = $_POST['beschreibung'] ?? '';
    $zubereitung = $_POST['zubereitung'] ?? '';
    $image = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];

    
    // Validate required fields
    if (!empty($title) && !empty($cuisine_id) && !empty($dauer) && !empty($kalorien)) {
        try {
            
            $query = "INSERT INTO gericht (title, cuisine_id, ernaehrungsweise_id, zubereitungszeit_min, kalorien, beschreibung, zubereitung) 
                      VALUES (:title, :cuisine_id, :ernaehrungsweise_id, :zubereitungszeit_min, :kalorien, :beschreibung, :zubereitung)";

            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':title' => $title,
                ':cuisine_id' => $cuisine_id,
                ':ernaehrungsweise_id' => $ernaehrungsweise_id,
                ':zubereitungszeit_min' => $dauer,
                ':kalorien' => $kalorien,
                ':beschreibung' => $beschreibung,
                ':zubereitung' => $zubereitung
            ]);
            
            
            $gericht_id = $conn->lastInsertId();
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                
                $upload_dir = './uploads'; 
                $image_info = $_FILES['image'];

                $image_extension = pathinfo($image_info['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('img_', true) . '.' . $image_extension;
                $target_path = $upload_dir . $new_filename;

                // Datei in den Zielordner verschieben
                if (move_uploaded_file($image_info['tmp_name'], $target_path)) {
                    $photo_query = "INSERT INTO foto (title, datei, erstellungsdaum, gericht_id) 
                                    VALUES (:title, :datei, NOW(), :gericht_id)";
                    
                    $photo_stmt = $conn->prepare($photo_query);
                    $photo_stmt->execute([
                        ':title' => $title,       
                        ':datei' => $target_path, 
                        ':gericht_id' => $gericht_id
                    ]);

                } else {
                    
                    $error_message = "Fehler beim Speichern des Bildes.";
                }
            }
            
           



            if (!empty($_POST['lebensmittel']) && is_array($_POST['lebensmittel'])) {
                
                $lebensmittel_ids = $_POST['lebensmittel'];
                $mengen = $_POST['menge'];
                $einheiten = $_POST['einheit'];
                
                
                $ingredient_query = "INSERT INTO gericht_lebensmittel (gericht_id, lebensmittel_id, menge, einheit) 
                                     VALUES (:gericht_id, :lebensmittel_id, :menge, :einheit)"; 
                $ingredient_stmt = $conn->prepare($ingredient_query);
                
                
                for ($i = 0; $i < count($lebensmittel_ids); $i++) {
                    
                    if (!empty($lebensmittel_ids[$i])) {
                        
                        $menge = $mengen[$i] ;
                        $einheit = $einheiten[$i];
                        $ingredient_stmt->execute([
                            ':gericht_id' => $gericht_id,
                            ':lebensmittel_id' => $lebensmittel_ids[$i],
                            ':menge' => $menge,
                            ':einheit' => $einheit]);
                    }
                }
            }
            
            $success_message = "Rezept wurde erfolgreich mit ID $gericht_id gespeichert!";

        } catch (PDOException $e) {
            $error_message = "Datenbankfehler: " . $e->getMessage();
        }
    } else {
        $error_message = "Bitte füllen Sie alle Pflichtfelder aus.";
    }
}

// Fetch cuisines from database
$cuisines = [];
try {
    $cuisine_query = "SELECT id, title FROM cuisine ORDER BY title";
    $cuisine_stmt = $conn->prepare($cuisine_query);
    $cuisine_stmt->execute();
    $cuisines = $cuisine_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Fehler beim Laden der Küchen: " . $e->getMessage();
}

// Fetch ernährungsweise from database
$ernaehrungsweisen = [];
try {
    $ernaehrung_query = "SELECT id, title FROM ernaehrungsweise ORDER BY title";
    $ernaehrung_stmt = $conn->prepare($ernaehrung_query);
    $ernaehrung_stmt->execute();
    $ernaehrungsweisen = $ernaehrung_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Fehler beim Laden der Ernährungsweisen: " . $e->getMessage();
}
// Fetch lebensmittel from database
$lebensmittel = [];
try {
    $lebensmittel_query = "SELECT id, title FROM lebensmittel ORDER BY title";
    $lebensmittel_stmt = $conn->prepare($lebensmittel_query);
    $lebensmittel_stmt->execute();
    $lebensmittel = $lebensmittel_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Fehler beim Laden der Lebensmittel: " . $e->getMessage();
}

// Fetch gewuerz from database
$gewuerz = [];
try {
    $gewuerz_query = "SELECT id, title FROM gewuerz ORDER BY title";
    $gewuerz_stmt = $conn->prepare($gewuerz_query);
    $gewuerz_stmt->execute();
    $gewuerz = $gewuerz_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Fehler beim Laden der Gewürze: " . $e->getMessage();
}

?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h1 class="card-title text-center text-primary mb-4 fw-bold">Neues Rezept hinzufügen</h1>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="row g-4" enctype="multipart/form-data">
                        <!-- Reihe 1: Grundinformationen -->
                        <div class="col-md-12">
                            <label for="gerichtsname" class="form-label fw-semibold">Gerichtsname *</label>
                            <input type="text" class="form-control" id="gerichtsname" name="gerichtsname" 
                                   placeholder="z.B. Omas Apfelkuchen" required 
                                   value="<?php echo htmlspecialchars($_POST['gerichtsname'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="cuisine" class="form-label fw-semibold">Cuisine *</label>
                            <select id="cuisine" name="cuisine" class="form-select" required>
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($cuisines as $cuisine): ?>
                                    <option value="<?php echo $cuisine['id']; ?>" 
                                            <?php echo (isset($_POST['cuisine']) && $_POST['cuisine'] == $cuisine['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cuisine['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="ernaehrungsweise" class="form-label fw-semibold">Ernährungsweise</label>
                            <select id="ernaehrungsweise" name="ernaehrungsweise" class="form-select">
                                <option value="">Bitte wählen...</option>
                                <?php foreach ($ernaehrungsweisen as $ernaehrung): ?>
                                    <option value="<?php echo $ernaehrung['id']; ?>" 
                                            <?php echo (isset($_POST['ernaehrungsweise']) && $_POST['ernaehrungsweise'] == $ernaehrung['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ernaehrung['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Reihe 2: Zeit & Kalorien -->
                        <div class="col-md-6">
                            <label for="dauer" class="form-label fw-semibold">Dauer (in Minuten) *</label>
                            <input type="number" class="form-control" id="dauer" name="dauer" 
                                   placeholder="z.B. 45" required min="1"
                                   value="<?php echo htmlspecialchars($_POST['dauer'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="kalorien" class="form-label fw-semibold">Kalorien (pro Portion) *</label>
                            <input type="number" class="form-control" id="kalorien" name="kalorien" 
                                   placeholder="z.B. 550" required min="1"
                                   value="<?php echo htmlspecialchars($_POST['kalorien'] ?? ''); ?>">
                        </div>

                        <!-- Sektion 3: Zutaten -->
                        <div class="col-12">
                            <fieldset class="border p-3 rounded-3">
                                <legend class="fs-6 fw-semibold px-2">Zutaten</legend>
                                <div id="zutaten-liste">
                                    <!-- Erste Zutat-Zeile -->
                                    <div class="row g-2 mb-2 align-items-center">
                                        
                                        <div class="col-md-5">
                                            
                                            <select id="lebensmittel" name="lebensmittel[]" class="form-select">
                                                <option value="">Bitte wählen...</option>
                                                <?php foreach ($lebensmittel as $lebensmittels): ?>
                                                    <option value="<?php echo $lebensmittels['id']; ?>" 
                                                            <?php echo (isset($_POST['lebensmittel']) && $_POST['lebensmittel'] == $lebensmittels['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($lebensmittels['title']); ?>
                                                        <?php endforeach; ?>
                                                        <br><h5>Gewürze</h5>
                                                        <?php foreach ($gewuerz as $gewuerze): ?>
                                                        <option value="<?php echo $gewuerze['id']; ?>" 
                                                                <?php echo (isset($_POST['gewuerz']) && $_POST['gewuerz'] == $gewuerze['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($gewuerze['title']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </option>
                                            </select>
                                        </div>
                                       
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control" name="menge[]" placeholder="Menge">
                                        </div>
                                        <div class="col-sm-4">
                                            <select class="form-select" name="einheit[]">
                                                <option value="g">Gramm (g)</option>
                                                <option value="kg">Kilogramm (kg)</option>
                                                <option value="ml">Milliliter (ml)</option>
                                                <option value="l">Liter (l)</option>
                                                <option value="Stk.">Stück</option>
                                                <option value="EL">Esslöffel</option>
                                                <option value="TL">Teelöffel</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" id="add-zutat-btn" class="btn btn-sm btn-success mt-2">
                                    <i class="bi bi-plus-circle me-1"></i> Weitere Zutat
                                </button>
                            </fieldset>
                        </div>

                        <!-- Reihe 4: Beschreibung -->
                        <div class="col-12">
                            <label for="beschreibung" class="form-label fw-semibold">Beschreibung</label>
                            <textarea class="form-control" id="beschreibung" name="beschreibung" rows="3" 
                                    placeholder="Kurze Beschreibung des Gerichts..."><?php echo htmlspecialchars($_POST['beschreibung'] ?? ''); ?></textarea>
                                
                                    <div class="col-12">
                                        <label for="image" class="form-label fw-semibold">Foto hochladen</label>
                                        <input class="form-control" type="file" id="image" name="image" accept="image/jpeg, image/png, image/gif">
                                    </div>
                        </div>
                        
                        <!-- Reihe 5: Zubereitung -->
                        <div class="col-12">
                            <label for="zubereitung" class="form-label fw-semibold">Zubereitung</label>
                            <textarea class="form-control" id="zubereitung" name="zubereitung" rows="5" 
                                      placeholder="Beschreibe hier die Zubereitungsschritte..."><?php echo htmlspecialchars($_POST['zubereitung'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Reihe 6: Absenden-Button -->
                        <div class="col-12 mt-5">
                            <button type="submit" class="btn btn-gradient-primary w-100 p-3 fs-5 fw-bold rounded-3">
                                Rezept speichern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Bootstrap 5 JS Bundle (via CDN) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
    // --- Dynamisches Hinzufügen von Zutatenfeldern ---
    const addZutatButton = document.getElementById('add-zutat-btn');
    const zutatenListe = document.getElementById('zutaten-liste');

    addZutatButton.addEventListener('click', function() {
        
        const neueZeile = document.createElement('div');
        neueZeile.className = 'row g-2 mb-2 align-items-center';

        
        neueZeile.innerHTML = `
            <div class="col-md-5">
                <select id="lebensmittel" name="lebensmittel[]" class="form-select">
                    <option value="">Bitte wählen...</option>
                    <?php foreach ($lebensmittel as $lebensmittels): ?>
                        <option value="<?php echo $lebensmittels['id']; ?>" 
                            <?php echo (isset($_POST['lebensmittel']) && $_POST['lebensmittel'] == $lebensmittels['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lebensmittels['title']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php foreach ($gewuerz as $gewuerze): ?>
                        <option value="<?php echo $gewuerze['id']; ?>" 
                            <?php echo (isset($_POST['gewuerz']) && $_POST['gewuerz'] == $gewuerze['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($gewuerze['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <input type="text" class="form-control" name="menge[]" placeholder="Menge">
            </div>
            <div class="col-sm-4">
                <select class="form-select" name="einheit[]">
                    <option value="g">Gramm (g)</option>
                    <option value="kg">Kilogramm (kg)</option>
                    <option value="ml">Milliliter (ml)</option>
                    <option value="l">Liter (l)</option>
                    <option value="Stk.">Stück</option>
                    <option value="EL">Esslöffel</option>
                    <option value="TL">Teelöffel</option>
                </select>
            </div>
            <div class="col-sm-12 text-end">
                <button type="button" class="btn btn-sm btn-danger remove-zutat-btn">
                    <i class="bi bi-trash"></i> Entfernen
                </button>
            </div>
        `;

        
        zutatenListe.appendChild(neueZeile);
    });

    
    zutatenListe.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-zutat-btn') || e.target.closest('.remove-zutat-btn')) {
            e.target.closest('.row').remove();
        }
    });
</script>

</body>
</html>