<?php
if (isset($_GET['show_code']) && $_GET['show_code'] === '1') {
    highlight_file(__FILE__); // Mostra o próprio código fonte formatado
} else {
    echo "<h1>Código Oculto</h1>";
    echo "<p>O código está oculto. Para exibir, adicione <code>?show_code=1</code> na URL.</p>";
}
