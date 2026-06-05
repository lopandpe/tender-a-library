<?php
/**
 * Tender Library - Dashboard Page
 * 
 * Displays a comprehensive overview of the "Tender Library" plugin
 * with information about functionalities, blocks, configuration pages, and more.
 * 
 * @package Tender_A_Library
 * @since 0.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

function tender_library_dashboard()
{
	if (!tender_user_can_access_library()) {
		wp_die(__('You do not have permission to access this page.', 'tender-library'));
	}
	
	// Enqueue dashboard styles
	wp_enqueue_style('tender-dashboard-styles');
	wp_enqueue_script('tender-dashboard-scripts');
?>
	<!DOCTYPE html>
	<html lang="<?php echo esc_attr(get_locale()); ?>">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php _e('Tender Library - Dashboard', 'tender-library'); ?></title>
		<style>
			* {
				margin: 0;
				padding: 0;
				box-sizing: border-box;
			}

			body.wp-admin {
				background: #f5f5f5 !important;
			}

			.tender-dashboard-container {
				max-width: 1200px;
				margin: 20px auto;
				padding: 0 20px;
			}

			.tender-header {
				background: white;
				padding: 40px;
				border-radius: 10px;
				margin-bottom: 30px;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				text-align: center;
				border-left: 5px solid #667eea;
			}

			.tender-header h1 {
				color: #667eea;
				font-size: 2em;
				margin-bottom: 10px;
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 15px;
			}

			.tender-header p {
				color: #666;
				font-size: 1em;
				max-width: 600px;
				margin: 0 auto;
			}

			.tender-plugin-info {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 20px;
				margin-top: 20px;
			}

			.tender-info-card {
				background: #f8f9fa;
				padding: 15px;
				border-radius: 8px;
				text-align: center;
				border-left: 4px solid #667eea;
			}

			.tender-info-card label {
				font-weight: 600;
				color: #667eea;
				display: block;
				font-size: 0.85em;
				text-transform: uppercase;
				letter-spacing: 1px;
				margin-bottom: 5px;
			}

			.tender-info-card span {
				display: block;
				color: #333;
				font-size: 1em;
				font-weight: 500;
			}

			.tender-section {
				background: white;
				padding: 30px;
				border-radius: 10px;
				margin-bottom: 30px;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				border-left: 5px solid #667eea;
			}

			.tender-section h2 {
				color: #667eea;
				font-size: 1.5em;
				margin-bottom: 20px;
				padding-bottom: 10px;
				border-bottom: 2px solid #f0f0f0;
			}

			.tender-section h3 {
				color: #764ba2;
				font-size: 1.2em;
				margin-top: 20px;
				margin-bottom: 15px;
			}

			.tender-section h4 {
				color: #555;
				font-size: 1em;
				margin-top: 15px;
				margin-bottom: 10px;
				font-weight: 600;
			}

			.tender-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
				gap: 20px;
				margin: 20px 0;
			}

			.tender-card {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 20px;
				border-radius: 8px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
				transition: transform 0.3s ease, box-shadow 0.3s ease;
			}

			.tender-card:hover {
				transform: translateY(-3px);
				box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
			}

			.tender-card h4 {
				font-size: 1.1em;
				margin-bottom: 10px;
				color: white;
			}

			.tender-card p {
				margin: 0;
				font-size: 0.9em;
				line-height: 1.5;
				opacity: 0.95;
			}

			.tender-feature-list {
				list-style: none;
				margin: 15px 0;
			}

			.tender-feature-list li {
				padding: 10px 0;
				padding-left: 30px;
				position: relative;
				color: #555;
				line-height: 1.6;
				font-size: 0.95em;
			}

			.tender-feature-list li:before {
				content: "✓";
				position: absolute;
				left: 0;
				color: #667eea;
				font-weight: bold;
				font-size: 1.2em;
			}

			.tender-badge {
				display: inline-block;
				background: #667eea;
				color: white;
				padding: 4px 12px;
				border-radius: 20px;
				font-size: 0.8em;
				font-weight: 600;
				margin: 5px 5px 5px 0;
			}

			.tender-status-badge {
				display: inline-block;
				padding: 5px 12px;
				border-radius: 5px;
				font-size: 0.8em;
				font-weight: 600;
				margin: 0 5px 0 0;
				background: #d4edda;
				color: #155724;
			}

			.tender-menu-structure {
				background: #f8f9fa;
				padding: 15px;
				border-radius: 8px;
				font-family: 'Courier New', monospace;
				margin: 15px 0;
				border-left: 4px solid #667eea;
				font-size: 0.9em;
			}

			.tender-menu-item {
				padding: 8px 0;
				color: #333;
			}

			.tender-menu-item.submenu {
				padding-left: 30px;
				color: #666;
			}

			.tender-menu-item.submenu:before {
				content: "↳ ";
				color: #667eea;
			}

			.tender-roles-list {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin: 15px 0;
			}

			.tender-role-badge {
				background: #e7f3ff;
				border: 1px solid #667eea;
				color: #667eea;
				padding: 6px 12px;
				border-radius: 5px;
				font-weight: 500;
				font-size: 0.85em;
			}

			.tender-tech-stack {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
				gap: 15px;
				margin: 20px 0;
			}

			.tender-process-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
				gap: 16px;
				margin: 20px 0;
			}

			.tender-process-step {
				background: #f8f9fa;
				padding: 18px;
				border-radius: 8px;
				border-left: 4px solid #667eea;
			}

			.tender-process-step strong {
				display: block;
				color: #667eea;
				margin-bottom: 8px;
				font-weight: 600;
			}

			.tender-process-step p {
				color: #555;
				line-height: 1.6;
				margin: 0;
			}

			.tender-tech-item {
				background: #f8f9fa;
				padding: 15px;
				border-radius: 8px;
				border-left: 4px solid #667eea;
			}

			.tender-tech-item strong {
				display: block;
				color: #667eea;
				margin-bottom: 5px;
				font-weight: 600;
			}

			.tender-tech-item span {
				display: block;
				color: #666;
				font-size: 0.85em;
				line-height: 1.4;
			}

			.tender-accordion {
				margin: 20px 0;
			}

			.tender-accordion-item {
				background: white;
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				margin-bottom: 10px;
				overflow: hidden;
			}

			.tender-accordion-title {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 15px 20px;
				cursor: pointer;
				font-weight: 600;
				display: flex;
				justify-content: space-between;
				align-items: center;
				transition: background 0.3s ease;
				font-size: 0.95em;
			}

			.tender-accordion-title:hover {
				opacity: 0.9;
			}

			.tender-accordion-content {
				padding: 20px;
				display: none;
				animation: tender-slideDown 0.3s ease;
			}

			.tender-accordion-item.active .tender-accordion-content {
				display: block;
			}

			.tender-accordion-toggle {
				font-size: 1.3em;
				transition: transform 0.3s ease;
			}

			.tender-accordion-item.active .tender-accordion-toggle {
				transform: rotate(45deg);
			}

			@keyframes tender-slideDown {
				from {
					opacity: 0;
					transform: translateY(-10px);
				}
				to {
					opacity: 1;
					transform: translateY(0);
				}
			}

			.tender-footer {
				background: white;
				padding: 30px;
				border-radius: 10px;
				text-align: center;
				color: #666;
				box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
				margin-top: 30px;
				border-left: 5px solid #667eea;
			}

			.tender-footer a {
				color: #667eea;
				text-decoration: none;
			}

			.tender-footer a:hover {
				text-decoration: underline;
			}

			code {
				background: #f8f9fa;
				padding: 2px 6px;
				border-radius: 4px;
				font-family: 'Courier New', monospace;
				color: #764ba2;
				font-size: 0.9em;
			}

			.tender-table {
				width: 100%;
				border-collapse: collapse;
				margin: 20px 0;
				font-size: 0.9em;
			}

			.tender-table th,
			.tender-table td {
				padding: 12px;
				text-align: left;
				border-bottom: 1px solid #e0e0e0;
			}

			.tender-table th {
				background: #f8f9fa;
				font-weight: 600;
				color: #667eea;
			}

			.tender-table tr:hover {
				background: #f8f9fa;
			}

			.tender-nav-links {
				background: #f8f9fa;
				padding: 20px;
				border-radius: 8px;
				margin: 20px 0;
				display: flex;
				gap: 15px;
				flex-wrap: wrap;
			}

			.tender-nav-links a {
				display: inline-block;
				background: #667eea;
				color: white;
				padding: 10px 20px;
				border-radius: 5px;
				text-decoration: none;
				font-weight: 600;
				font-size: 0.9em;
				transition: background 0.3s ease;
			}

			.tender-nav-links a:hover {
				background: #764ba2;
			}

			@media (max-width: 768px) {
				.tender-section {
					padding: 20px;
				}

				.tender-grid {
					grid-template-columns: 1fr;
				}

				.tender-header h1 {
					font-size: 1.5em;
					flex-direction: column;
				}

				.tender-section h2 {
					font-size: 1.3em;
				}
			}
		</style>
	</head>
	<body>
		<div class="tender-dashboard-container">
			<!-- HEADER -->
			<div class="tender-header">
				<h1>
					<span>📚</span>
					<?php _e('Tender Library', 'tender-library'); ?>
				</h1>
				<p><?php _e('Adds library functionality to the core of WordPress, including books, readers, loans, and more.', 'tender-library'); ?></p>
				
				<div class="tender-plugin-info">
					<div class="tender-info-card">
						<label><?php _e('Version', 'tender-library'); ?></label>
						<span><?php echo esc_html(defined('TENDER_LIBRARY_VERSION') ? TENDER_LIBRARY_VERSION : '1.0.0'); ?></span>
					</div>
					<div class="tender-info-card">
						<label><?php _e('Author', 'tender-library'); ?></label>
						<span><?php _e('Local Anarquista Magdalena', 'tender-library'); ?></span>
					</div>
					<div class="tender-info-card">
						<label><?php _e('WordPress', 'tender-library'); ?></label>
						<span>6.5+</span>
					</div>
					<div class="tender-info-card">
						<label><?php _e('PHP', 'tender-library'); ?></label>
						<span>5.6+</span>
					</div>
				</div>
			</div>

			<!-- QUICK NAVIGATION -->
			<div class="tender-section">
				<h2><?php _e('Quick Navigation', 'tender-library'); ?></h2>
				<div class="tender-nav-links">
					<a href="<?php echo esc_url(admin_url('admin.php?page=tender-lendings')); ?>">📋 <?php _e('View Active Loans', 'tender-library'); ?></a>
					<a href="<?php echo esc_url(admin_url('admin.php?page=tender-old-lendings')); ?>">✓ <?php _e('View Completed Loans', 'tender-library'); ?></a>
					<a href="<?php echo esc_url(admin_url('edit.php?post_type=tender_book')); ?>">📖 <?php _e('Manage Books', 'tender-library'); ?></a>
				</div>
			</div>

			<!-- WHAT'S INCLUDED -->
			<div class="tender-section">
				<h2>🧩 <?php _e('What The Plugin Includes', 'tender-library'); ?></h2>
				<p><?php _e('Tender A Library is not a single screen. It combines catalog management, reader profiles, lending operations, search components, content blocks, and migration tools into one workflow for running a small community library inside WordPress.', 'tender-library'); ?></p>

				<div class="tender-grid">
					<div class="tender-card">
						<h4><?php _e('Catalog', 'tender-library'); ?></h4>
						<p><?php _e('Books as a custom post type, with structured metadata, cover images, signatures, sections, languages, and reusable content blocks.', 'tender-library'); ?></p>
					</div>
					<div class="tender-card">
						<h4><?php _e('People', 'tender-library'); ?></h4>
						<p><?php _e('Reader, opener, librarian, editor, and administrator flows with profile pages, profile editing, and permission-aware visibility.', 'tender-library'); ?></p>
					</div>
					<div class="tender-card">
						<h4><?php _e('Lending Operations', 'tender-library'); ?></h4>
						<p><?php _e('Active lendings, completed lendings, renewals, returns, reservations, call logs, and reminder emails for day-to-day circulation work.', 'tender-library'); ?></p>
					</div>
					<div class="tender-card">
						<h4><?php _e('Import And Migration', 'tender-library'); ?></h4>
						<p><?php _e('CSV tools for importing books, users, lendings, and calls, including automatic creation of sections, languages, and book cover media.', 'tender-library'); ?></p>
					</div>
				</div>
			</div>

			<!-- HOW IT WORKS -->
			<div class="tender-section">
				<h2>⚙️ <?php _e('How It Works', 'tender-library'); ?></h2>
				<p><?php _e('The plugin is designed around a simple operating cycle: define your catalog structure, register books and readers, circulate books through lendings, and keep the public-facing library searchable and reusable in pages and templates.', 'tender-library'); ?></p>

				<div class="tender-process-grid">
					<div class="tender-process-step">
						<strong><?php _e('1. Configure the library', 'tender-library'); ?></strong>
						<p><?php _e('Set up pages, permalinks, roles, and taxonomy structure so the catalog and profile routes are ready to use.', 'tender-library'); ?></p>
					</div>
					<div class="tender-process-step">
						<strong><?php _e('2. Load the catalog', 'tender-library'); ?></strong>
						<p><?php _e('Create books manually or import them from CSV. During migration, the plugin can also create sections, languages, and download cover images.', 'tender-library'); ?></p>
					</div>
					<div class="tender-process-step">
						<strong><?php _e('3. Manage readers', 'tender-library'); ?></strong>
						<p><?php _e('Readers get profile pages, while openers and administrators can review user information, lending history, reservations, and follow-up calls.', 'tender-library'); ?></p>
					</div>
					<div class="tender-process-step">
						<strong><?php _e('4. Run circulation', 'tender-library'); ?></strong>
						<p><?php _e('Loans are created, renewed, returned, and archived through the lending tools, with availability and reservation state updating from those actions.', 'tender-library'); ?></p>
					</div>
					<div class="tender-process-step">
						<strong><?php _e('5. Publish the library', 'tender-library'); ?></strong>
						<p><?php _e('Use the included blocks, templates, and search interface to display books, events, and profile links across the frontend.', 'tender-library'); ?></p>
					</div>
					<div class="tender-process-step">
						<strong><?php _e('6. Maintain and report', 'tender-library'); ?></strong>
						<p><?php _e('Use exports, reminders, completed lending history, and call logs to keep operations tidy and visible over time.', 'tender-library'); ?></p>
					</div>
				</div>
			</div>

			<!-- MAIN FEATURES SECTION -->
			<div class="tender-section">
				<h2>✨ <?php _e('Main Features', 'tender-library'); ?></h2>
				<ul class="tender-feature-list">
					<li><?php _e('Complete book management system with custom fields and taxonomies', 'tender-library'); ?></li>
					<li><?php _e('Advanced lending system with tracking and history', 'tender-library'); ?></li>
					<li><?php _e('Reservation system for unavailable books', 'tender-library'); ?></li>
					<li><?php _e('User profile management with custom permissions', 'tender-library'); ?></li>
					<li><?php _e('Advanced search functionality with filters and API', 'tender-library'); ?></li>
					<li><?php _e('Customizable Gutenberg blocks for content display', 'tender-library'); ?></li>
					<li><?php _e('Email notifications for loan reminders and reservations', 'tender-library'); ?></li>
					<li><?php _e('CSV export functionality for lending reports', 'tender-library'); ?></li>
					<li><?php _e('Role-based access control with multiple user roles', 'tender-library'); ?></li>
					<li><?php _e('Internationalization support (Spanish included)', 'tender-library'); ?></li>
				</ul>
			</div>

			<!-- GUTENBERG BLOCKS SECTION -->
			<div class="tender-section">
				<h2>🎨 <?php _e('Gutenberg Blocks', 'tender-library'); ?></h2>
				<p><?php _e('The plugin includes 7 custom Gutenberg blocks for displaying library content:', 'tender-library'); ?></p>
				
				<div class="tender-grid">
					<div class="tender-card">
						<h4>📖 <?php _e('Book Summary Block', 'tender-library'); ?></h4>
						<p><?php _e('Displays a summary view of a book with title, author, and key information.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>🖼️ <?php _e('Book Cover Block', 'tender-library'); ?></h4>
						<p><?php _e('Shows the book cover image with customizable sizing and styling options.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>📊 <?php _e('Book Data Block', 'tender-library'); ?></h4>
						<p><?php _e('Displays detailed book information including ISBN, publisher, publication date, and more.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>📕 <?php _e('Mini Book Block', 'tender-library'); ?></h4>
						<p><?php _e('A compact book preview card ideal for listings and collections.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>🔍 <?php _e('Book Search Block', 'tender-library'); ?></h4>
						<p><?php _e('Interactive search component to find books with advanced filtering options.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>📅 <?php _e('Upcoming Events Block', 'tender-library'); ?></h4>
						<p><?php _e('Shows upcoming library events and activities.', 'tender-library'); ?></p>
					</div>

					<div class="tender-card">
						<h4>👤 <?php _e('Profile Links Block', 'tender-library'); ?></h4>
						<p><?php _e('Displays user profile navigation links for quick access.', 'tender-library'); ?></p>
					</div>
				</div>

				<p style="margin-top: 20px; color: #666;">
					<strong><?php _e('Block Category:', 'tender-library'); ?></strong>
					<span class="tender-badge">Tender Blocks</span>
				</p>
			</div>

			<!-- ADMIN MENU STRUCTURE -->
			<div class="tender-section">
				<h2>🧭 <?php _e('Admin Menu Structure', 'tender-library'); ?></h2>
				<p><?php _e('The plugin adds a main "Biblioteca" menu to the WordPress admin with the following structure:', 'tender-library'); ?></p>
				
				<div class="tender-menu-structure">
					<div class="tender-menu-item">📚 Biblioteca</div>
					<div class="tender-menu-item submenu">Préstamos Activos (<?php _e('Active Loans', 'tender-library'); ?>)</div>
					<div class="tender-menu-item submenu">Préstamos Terminados (<?php _e('Completed Loans', 'tender-library'); ?>)</div>
				</div>

				<h3><?php _e('Access Requirements', 'tender-library'); ?></h3>
				<p><?php _e('The library menu is visible only to users with the following roles:', 'tender-library'); ?></p>
				<div class="tender-roles-list">
					<span class="tender-role-badge">Administrator</span>
					<span class="tender-role-badge">Editor</span>
					<span class="tender-role-badge">Opener</span>
					<span class="tender-role-badge">Librarian</span>
				</div>
			</div>

			<!-- CUSTOM POST TYPES -->
			<div class="tender-section">
				<h2>📝 <?php _e('Custom Post Types & Taxonomies', 'tender-library'); ?></h2>
				
				<div class="tender-accordion">
					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('📖 Books (CPT: book)', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Main post type for managing library books with extensive metadata.', 'tender-library'); ?></p>
							<h4><?php _e('Custom Fields:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('ISBN - International Standard Book Number', 'tender-library'); ?></li>
								<li><?php _e('Author(s) - Book authors', 'tender-library'); ?></li>
								<li><?php _e('Publisher - Publishing company', 'tender-library'); ?></li>
								<li><?php _e('Publication Date - Release date', 'tender-library'); ?></li>
								<li><?php _e('Number of Pages - Book length', 'tender-library'); ?></li>
								<li><?php _e('Language - Book language', 'tender-library'); ?></li>
								<li><?php _e('Edition - Book edition', 'tender-library'); ?></li>
								<li><?php _e('Book Cover - Featured image', 'tender-library'); ?></li>
								<li><?php _e('Availability Status - Available/Unavailable', 'tender-library'); ?></li>
								<li><?php _e('Physical Location - Shelf location', 'tender-library'); ?></li>
							</ul>
							<h4><?php _e('Taxonomies:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Section - Library section/category', 'tender-library'); ?></li>
								<li><?php _e('Genre - Book genre', 'tender-library'); ?></li>
								<li><?php _e('Subject - Subject matter', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🔄 Lendings (CPT: lending)', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Post type for managing book loans and lending history.', 'tender-library'); ?></p>
							<h4><?php _e('Tracked Information:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Book ID - Reference to borrowed book', 'tender-library'); ?></li>
								<li><?php _e('User/Reader - Person who borrowed the book', 'tender-library'); ?></li>
								<li><?php _e('Loan Date - Date of borrowing', 'tender-library'); ?></li>
								<li><?php _e('Due Date - Expected return date', 'tender-library'); ?></li>
								<li><?php _e('Return Date - Actual return date', 'tender-library'); ?></li>
								<li><?php _e('Loan Status - Active/Returned/Overdue', 'tender-library'); ?></li>
								<li><?php _e('Notes - Lending notes', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('📅 Events (CPT: event)', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Post type for managing library events and activities.', 'tender-library'); ?></p>
							<h4><?php _e('Event Information:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Event Title', 'tender-library'); ?></li>
								<li><?php _e('Event Date & Time', 'tender-library'); ?></li>
								<li><?php _e('Location', 'tender-library'); ?></li>
								<li><?php _e('Description', 'tender-library'); ?></li>
								<li><?php _e('Featured Image', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<!-- DATABASE TABLES -->
			<div class="tender-section">
				<h2>🗄️ <?php _e('Database Tables', 'tender-library'); ?></h2>
				<p><?php _e('The plugin creates the following custom database tables:', 'tender-library'); ?></p>
				
				<table class="tender-table">
					<thead>
						<tr>
							<th><?php _e('Table Name', 'tender-library'); ?></th>
							<th><?php _e('Purpose', 'tender-library'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>wp_tender_lendings</code></td>
							<td><?php _e('Stores lending/loan records', 'tender-library'); ?></td>
						</tr>
						<tr>
							<td><code>wp_tender_reservations</code></td>
							<td><?php _e('Stores book reservations', 'tender-library'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- FRONTEND PAGES -->
			<div class="tender-section">
				<h2>🌐 <?php _e('Frontend Pages', 'tender-library'); ?></h2>
				<p><?php _e('The plugin creates and manages the following frontend pages:', 'tender-library'); ?></p>
				
				<div class="tender-accordion">
					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('👤 User Profile Page', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Displays user profile information with viewing and editing capabilities.', 'tender-library'); ?></p>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('User profile information display', 'tender-library'); ?></li>
								<li><?php _e('Login required for access', 'tender-library'); ?></li>
								<li><?php _e('View other users\' public profiles', 'tender-library'); ?></li>
								<li><?php _e('Permission-based profile access', 'tender-library'); ?></li>
								<li><?php _e('Personalized user information', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('✏️ Edit Profile Page', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Allows users to edit their profile information.', 'tender-library'); ?></p>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Edit personal information', 'tender-library'); ?></li>
								<li><?php _e('Upload profile picture', 'tender-library'); ?></li>
								<li><?php _e('Manage contact information', 'tender-library'); ?></li>
								<li><?php _e('Role-based edit permissions', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('👥 Users List Page', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Displays a list of all library users/readers.', 'tender-library'); ?></p>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('User directory listing', 'tender-library'); ?></li>
								<li><?php _e('Searchable user list', 'tender-library'); ?></li>
								<li><?php _e('Links to user profiles', 'tender-library'); ?></li>
								<li><?php _e('Permission-based visibility', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🔍 Search Page', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<p><?php _e('Advanced book search with filtering capabilities.', 'tender-library'); ?></p>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Full-text book search', 'tender-library'); ?></li>
								<li><?php _e('Filter by genre, section, author', 'tender-library'); ?></li>
								<li><?php _e('Sort options (title, author, date)', 'tender-library'); ?></li>
								<li><?php _e('RESTful API endpoints for filters', 'tender-library'); ?></li>
								<li><?php _e('Pagination support', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<!-- FEATURES BY CATEGORY -->
			<div class="tender-section">
				<h2>⚙️ <?php _e('Core Functionalities', 'tender-library'); ?></h2>
				
				<div class="tender-accordion">
					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('📚 Book Management', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Create, edit, and delete books', 'tender-library'); ?></li>
								<li><?php _e('Assign books to sections and categories', 'tender-library'); ?></li>
								<li><?php _e('Upload and manage book covers', 'tender-library'); ?></li>
								<li><?php _e('Track ISBN and publication details', 'tender-library'); ?></li>
								<li><?php _e('Manage book availability status', 'tender-library'); ?></li>
								<li><?php _e('Full-text searchable book content', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🔄 Lending System', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Create and track book loans', 'tender-library'); ?></li>
								<li><?php _e('Set loan duration and due dates', 'tender-library'); ?></li>
								<li><?php _e('Track loan history per book and user', 'tender-library'); ?></li>
								<li><?php _e('Manage overdue loans', 'tender-library'); ?></li>
								<li><?php _e('Export lending data to CSV', 'tender-library'); ?></li>
								<li><?php _e('Record book returns', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🏷️ Reservation System', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Reserve unavailable books', 'tender-library'); ?></li>
								<li><?php _e('Track reservation queue', 'tender-library'); ?></li>
								<li><?php _e('Notify users when books become available', 'tender-library'); ?></li>
								<li><?php _e('Cancel reservations', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('👥 User Roles & Permissions', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<h4><?php _e('Custom Roles:', 'tender-library'); ?></h4>
							<div class="tender-roles-list">
								<span class="tender-role-badge">Librarian</span>
								<span class="tender-role-badge">Opener</span>
							</div>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('Custom role creation for librarians', 'tender-library'); ?></li>
								<li><?php _e('Custom role for users who open/close library', 'tender-library'); ?></li>
								<li><?php _e('Granular capability management', 'tender-library'); ?></li>
								<li><?php _e('Role-based content access restrictions', 'tender-library'); ?></li>
								<li><?php _e('Restrict admin dashboard for non-staff', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('📧 Email Notifications', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Overdue loan reminders', 'tender-library'); ?></li>
								<li><?php _e('Reservation availability notifications', 'tender-library'); ?></li>
								<li><?php _e('Loan confirmation emails', 'tender-library'); ?></li>
								<li><?php _e('Return reminder emails', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🔍 Search & Filter API', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<h4><?php _e('API Endpoints:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><code>/wp-json/tender/v1/search</code> - <?php _e('Book search endpoint', 'tender-library'); ?></li>
								<li><code>/wp-json/tender/v1/filters</code> - <?php _e('Available filters endpoint', 'tender-library'); ?></li>
							</ul>
							<h4><?php _e('Features:', 'tender-library'); ?></h4>
							<ul class="tender-feature-list">
								<li><?php _e('RESTful API for searching books', 'tender-library'); ?></li>
								<li><?php _e('Dynamic filter generation', 'tender-library'); ?></li>
								<li><?php _e('Support for complex queries', 'tender-library'); ?></li>
								<li><?php _e('URL parameter support for saved searches', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🎨 Styling & Theming', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Custom CSS for plugin pages', 'tender-library'); ?></li>
								<li><?php _e('Tailwind CSS integration', 'tender-library'); ?></li>
								<li><?php _e('Block styling support', 'tender-library'); ?></li>
								<li><?php _e('Responsive design', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🌍 Localization', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Spanish (es_ES) translation included', 'tender-library'); ?></li>
								<li><?php _e('Language files in /languages directory', 'tender-library'); ?></li>
								<li><?php _e('Mobile Object (MO) files for compiled translations', 'tender-library'); ?></li>
								<li><?php _e('Text domain: tender-library', 'tender-library'); ?></li>
								<li><?php _e('WordPress Locale support', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>

					<div class="tender-accordion-item">
						<div class="tender-accordion-title">
							<span><?php _e('🔗 Permalink & URL Routing', 'tender-library'); ?></span>
							<span class="tender-accordion-toggle">+</span>
						</div>
						<div class="tender-accordion-content">
							<ul class="tender-feature-list">
								<li><?php _e('Custom permalink structures', 'tender-library'); ?></li>
								<li><?php _e('Query variable registration', 'tender-library'); ?></li>
								<li><?php _e('Rewrite rule management', 'tender-library'); ?></li>
								<li><?php _e('Clean URL support', 'tender-library'); ?></li>
							</ul>
						</div>
					</div>
				</div>
			</div>

			<!-- TECHNICAL SPECIFICATIONS -->
			<div class="tender-section">
				<h2>🛠️ <?php _e('Technical Stack', 'tender-library'); ?></h2>
				
				<div class="tender-tech-stack">
					<div class="tender-tech-item">
						<strong><?php _e('Frontend', 'tender-library'); ?></strong>
						<span>React, Gutenberg Blocks, Tailwind CSS</span>
					</div>
					<div class="tender-tech-item">
						<strong><?php _e('Backend', 'tender-library'); ?></strong>
						<span>PHP, WordPress REST API, Carbon Fields</span>
					</div>
					<div class="tender-tech-item">
						<strong><?php _e('Database', 'tender-library'); ?></strong>
						<span>MySQL, Custom Tables, WordPress Posts</span>
					</div>
					<div class="tender-tech-item">
						<strong><?php _e('Build Tools', 'tender-library'); ?></strong>
						<span>Webpack, Babel, npm, Grunt</span>
					</div>
					<div class="tender-tech-item">
						<strong><?php _e('Testing', 'tender-library'); ?></strong>
						<span>PHPUnit, Code Standards</span>
					</div>
					<div class="tender-tech-item">
						<strong><?php _e('Version Control', 'tender-library'); ?></strong>
						<span>Git, GitHub</span>
					</div>
				</div>

				<h3><?php _e('Key Dependencies:', 'tender-library'); ?></h3>
				<ul class="tender-feature-list">
					<li><?php _e('Carbon Fields - Advanced WordPress custom fields framework', 'tender-library'); ?></li>
					<li><?php _e('WordPress REST API - RESTful data access', 'tender-library'); ?></li>
					<li><?php _e('Gutenberg Blocks Editor - Block development framework', 'tender-library'); ?></li>
					<li><?php _e('Tailwind CSS - Utility-first CSS framework', 'tender-library'); ?></li>
				</ul>
			</div>

			<!-- INSTALLATION & ACTIVATION -->
			<div class="tender-section">
				<h2>⚡ <?php _e('Installation & Activation', 'tender-library'); ?></h2>
				
				<h3><?php _e('Automatic Setup', 'tender-library'); ?></h3>
				<p><?php _e('When the plugin is activated, the following happens automatically:', 'tender-library'); ?></p>
				<ul class="tender-feature-list">
					<li><?php _e('Creates necessary database tables', 'tender-library'); ?></li>
					<li><?php _e('Registers custom post types', 'tender-library'); ?></li>
					<li><?php _e('Creates default pages for profile, search, and users list', 'tender-library'); ?></li>
					<li><?php _e('Sets up rewrite rules for clean permalinks', 'tender-library'); ?></li>
					<li><?php _e('Registers custom roles and capabilities', 'tender-library'); ?></li>
					<li><?php _e('Loads plugin modules and features', 'tender-library'); ?></li>
				</ul>

				<h3><?php _e('Plugin Hooks', 'tender-library'); ?></h3>
				<p><?php _e('Activation Hooks:', 'tender-library'); ?></p>
				<ul class="tender-feature-list">
					<li><code>tender_create_database_tables</code> - <?php _e('Creates custom database tables', 'tender-library'); ?></li>
					<li><code>tal_create_plugin_pages_on_activation</code> - <?php _e('Creates frontend pages', 'tender-library'); ?></li>
				</ul>
			</div>

			<!-- CONFIGURATION -->
			<div class="tender-section">
				<h2>⚙️ <?php _e('Configuration & Settings', 'tender-library'); ?></h2>
				
				<p><?php _e('The plugin stores configuration in WordPress options:', 'tender-library'); ?></p>
				
				<table class="tender-table">
					<thead>
						<tr>
							<th><?php _e('Option Key', 'tender-library'); ?></th>
							<th><?php _e('Description', 'tender-library'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>tal_profile_page</code></td>
							<td><?php _e('Page ID for user profile page', 'tender-library'); ?></td>
						</tr>
						<tr>
							<td><code>tal_search_page</code></td>
							<td><?php _e('Page ID for book search page', 'tender-library'); ?></td>
						</tr>
						<tr>
							<td><code>tal_users_list_page</code></td>
							<td><?php _e('Page ID for users list page', 'tender-library'); ?></td>
						</tr>
						<tr>
							<td><code>tal_edit_profile_page</code></td>
							<td><?php _e('Page ID for edit profile page', 'tender-library'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- SECURITY & BEST PRACTICES -->
			<div class="tender-section">
				<h2>🔒 <?php _e('Security & Best Practices', 'tender-library'); ?></h2>
				
				<ul class="tender-feature-list">
					<li><?php _e('WordPress nonce verification for form submissions', 'tender-library'); ?></li>
					<li><?php _e('Role-based access control throughout the plugin', 'tender-library'); ?></li>
					<li><?php _e('Sanitization and escaping of user input', 'tender-library'); ?></li>
					<li><?php _e('Proper capability checks for admin operations', 'tender-library'); ?></li>
					<li><?php _e('REST API authentication and permission checks', 'tender-library'); ?></li>
					<li><?php _e('Database prepared statements via WordPress functions', 'tender-library'); ?></li>
					<li><?php _e('ABSPATH check to prevent direct file access', 'tender-library'); ?></li>
				</ul>
			</div>

			<!-- FUTURE ENHANCEMENTS -->
			<div class="tender-section">
				<h2>🚀 <?php _e('Potential Future Enhancements', 'tender-library'); ?></h2>
				
				<ul class="tender-feature-list">
					<li><?php _e('Mobile app integration', 'tender-library'); ?></li>
					<li><?php _e('Barcode scanning for books and users', 'tender-library'); ?></li>
					<li><?php _e('Advanced reporting and analytics', 'tender-library'); ?></li>
					<li><?php _e('Integration with library management systems', 'tender-library'); ?></li>
					<li><?php _e('Social features (ratings, reviews, recommendations)', 'tender-library'); ?></li>
					<li><?php _e('Advanced statistics and usage tracking', 'tender-library'); ?></li>
					<li><?php _e('Wishlist and favorites functionality', 'tender-library'); ?></li>
					<li><?php _e('Integration with external book databases (Google Books, Open Library)', 'tender-library'); ?></li>
					<li><?php _e('Multi-language support expansion', 'tender-library'); ?></li>
					<li><?php _e('Advanced search filters and faceted navigation', 'tender-library'); ?></li>
				</ul>
			</div>

			<!-- FOOTER -->
			<div class="tender-footer">
				<h3><?php _e('Tender Library', 'tender-library'); ?></h3>
				<p><?php _e('A comprehensive library management system for WordPress', 'tender-library'); ?></p>
				<p>
					<?php printf(esc_html__('Version %s • ', 'tender-library'), esc_html(defined('TENDER_LIBRARY_VERSION') ? TENDER_LIBRARY_VERSION : '1.0.0')); ?>
					<a href="https://localanarquistamagdalena.org" target="_blank"><?php _e('Local Anarquista Magdalena', 'tender-library'); ?></a>
				</p>
				<p style="margin-top: 20px; font-size: 0.9em;">
					<?php _e('For more information or support, please contact the development team.', 'tender-library'); ?>
				</p>
			</div>
		</div>

		<script>
			(function() {
				// Accordion functionality
				const accordionTitles = document.querySelectorAll('.tender-accordion-title');
				accordionTitles.forEach(function(title) {
					title.addEventListener('click', function() {
						const item = this.closest('.tender-accordion-item');
						const isActive = item.classList.contains('active');
						
						// Close all other items
						document.querySelectorAll('.tender-accordion-item.active').forEach(function(activeItem) {
							if (activeItem !== item) {
								activeItem.classList.remove('active');
							}
						});
						
						// Toggle current item
						item.classList.toggle('active');
					});
				});
			})();
		</script>
	</body>
	</html>
<?php
}
