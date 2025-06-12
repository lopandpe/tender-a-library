<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}
function tender_library_dashboard()
{
	if (!tender_user_can_access_library()) {
		wp_die(__('You do not have permission to access this page.', 'tender-a-library'));
	}
?>
	<div class="wrap">
		<h1>Biblioteca - Panel de Control</h1>
		<p>Bienvenido a la gestión de préstamos de la biblioteca. Desde aquí puedes administrar los libros prestados y gestionar nuevos préstamos.</p>
		<ul>
			<li><a href="<?php echo admin_url('admin.php?page=tender-lendings'); ?>">Ver préstamos activos</a></li>
			<li><a href="<?php echo admin_url('admin.php?page=tender-new-lending'); ?>">Crear un nuevo préstamo</a></li>
		</ul>
	</div>
<?php
}
