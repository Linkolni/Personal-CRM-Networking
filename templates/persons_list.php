<!-- templates/persons_list.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Kontakte</h4>
   <button id="btn-add-person" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Neu
    </button>
</div>
    <!-- 
    HIER IST DIE ÄNDERUNG:
    Dieser neue Container wird von app.js genutzt, um die Filter-Badges einzufügen.
    --><p></p>
    <div id="circles-filter-container" class="mb-3">
        <!-- Wird von JS gefüllt -->
    </div>

 


<!-- 
    KORREKTE ID: 'persons-table-container'
    Dies ist der Platzhalter, den dein JavaScript sucht.
-->
<div id="persons-table-container">
    <!-- Wird von JS gefüllt -->
</div>