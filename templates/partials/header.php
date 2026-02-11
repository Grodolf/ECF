<div class="">
    <div class="flex ju-between it-center p">
        <img src="./img/LogoVG.svg" alt="Logo Vite et gourmand">
        <p id="headline"><?= htmlspecialchars($headline) ?></p>
        <button id="nav-button"><img src="./img/Menu.svg" alt=""></button>
    </div>

    <div id="nav-bar" class="flex ju-between it-center hidden">
        <div class="f-col ju-center it-center px g-">
            <p>Prêt à découvrir nos créations culinaires ?</p>
            <button class="btn">
                <p>Commander un menu</p>
            </button>
            <div class="flex it-center g- mt+">
                <label for="mode">Thème :</label>
                <select name="mode" id="mode">
                    <option value="auto" selected>Auto</option>
                    <option value="light">Clair</option>
                    <option value="dark">Sombre</option>
                </select>
            </div>
        </div>
        <nav>
            <?php include_once __DIR__ . '/nav.php' ?>
        </nav>
    </div>
</div>
