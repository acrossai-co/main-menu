<?php

namespace AcrossAI_Main_Menu;

/**
 * Renders the AcrossAI parent menu landing page.
 *
 * Self-contained: all CSS is emitted in a single <style> block in this file
 * so the page has no external CSS dependencies beyond the Google Fonts import.
 */
class DashboardRenderer {

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->print_styles();
		$this->print_markup();
	}

	private function print_styles(): void {
		?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600&display=swap');

.acai-dashboard, .acai-dashboard *, .acai-dashboard *::before, .acai-dashboard *::after { box-sizing: border-box; }
.acai-dashboard {
	--accent: #5538EE;
	font-family: 'IBM Plex Sans', system-ui, sans-serif;
	color: #15161B;
	background: #F4F5F7;
	min-height: calc(100vh - 32px);
	-webkit-font-smoothing: antialiased;
	background-image: radial-gradient(circle at 1px 1px, rgba(21,22,27,0.04) 1px, transparent 0);
	background-size: 22px 22px;
	margin: 10px -20px 0 -20px;
}
.acai-dashboard a { text-decoration: none; }
.acai-dashboard h1, .acai-dashboard h2, .acai-dashboard h3, .acai-dashboard p { margin: 0; }

.acai-shell { max-width: 1180px; margin: 0 auto; padding: 28px 32px 72px; }

/* Header */
.acai-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 8px; }
.acai-brand { display: flex; align-items: center; gap: 12px; }
.acai-brand-mark {
	width: 40px; height: 40px; border-radius: 11px;
	background: var(--accent); color: #fff;
	display: flex; align-items: center; justify-content: center;
	box-shadow: 0 6px 18px -6px var(--accent);
}
.acai-brand-text { display: flex; flex-direction: column; line-height: 1.05; }
.acai-brand-name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 19px; letter-spacing: -0.01em; }
.acai-brand-tag { font-size: 12px; color: #6B6F7B; font-weight: 500; }

.acai-social { display: flex; align-items: center; gap: 8px; }
.acai-social a {
	width: 38px; height: 38px; border-radius: 10px;
	border: 1px solid #E4E6EB; background: #fff; color: #4A4E5A;
	display: flex; align-items: center; justify-content: center;
	transition: border-color .15s, color .15s, transform .15s;
}
.acai-social a:hover { border-color: var(--accent); color: var(--accent); transform: translateY(-1px); }

/* Hero */
.acai-hero { position: relative; padding: 64px 0 52px; text-align: center; overflow: visible; }
.acai-hero-glow {
	position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
	width: 520px; height: 320px;
	background: radial-gradient(ellipse at center, color-mix(in oklab, var(--accent) 26%, transparent), transparent 70%);
	filter: blur(24px); z-index: 0; pointer-events: none;
	animation: acaiFloat 9s ease-in-out infinite;
}
.acai-hero-inner { position: relative; z-index: 1; }
.acai-pill {
	display: inline-flex; align-items: center; gap: 8px;
	padding: 6px 14px; border-radius: 999px;
	background: #fff; border: 1px solid #E4E6EB;
	font-size: 13px; font-weight: 500; color: #4A4E5A;
	margin-bottom: 22px;
}
.acai-pill-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
.acai-hero h1 {
	font-family: 'Space Grotesk', sans-serif; font-weight: 700;
	font-size: clamp(34px, 5.2vw, 56px); line-height: 1.04; letter-spacing: -0.025em;
	margin: 0 auto 20px; max-width: 760px; text-wrap: balance;
}
.acai-hero p {
	font-size: clamp(16px, 2vw, 18.5px); line-height: 1.6; color: #565B68;
	max-width: 620px; margin: 0 auto 30px; text-wrap: pretty;
}
.acai-chip-row { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
.acai-chip {
	padding: 7px 15px; border-radius: 999px; background: #fff;
	border: 1px solid #E4E6EB; font-size: 13.5px; font-weight: 500; color: #2C3038;
}

@keyframes acaiFloat { 0%,100% { transform: translate3d(-50%,0,0); } 50% { transform: translate3d(-50%,-14px,0); } }

/* Plugins section */
.acai-section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
.acai-section-head h2 {
	font-family: 'Space Grotesk', sans-serif; font-weight: 600; font-size: 15px;
	letter-spacing: 0.08em; text-transform: uppercase; color: #8A8E99;
}
.acai-section-meta { font-size: 13.5px; color: #8A8E99; }

.acai-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(290px, 1fr)); gap: 20px; }
.acai-card {
	background: #fff; border: 1px solid #E7E9EE; border-radius: 18px;
	padding: 28px 26px; display: flex; flex-direction: column;
	transition: transform .18s, box-shadow .18s, border-color .18s;
}
.acai-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 18px 40px -22px rgba(21,22,27,0.35);
	border-color: color-mix(in oklab, var(--accent) 40%, #E7E9EE);
}
.acai-card-icon {
	width: 54px; height: 54px; border-radius: 14px;
	background: color-mix(in oklab, var(--accent) 12%, #fff); color: var(--accent);
	display: flex; align-items: center; justify-content: center; margin-bottom: 20px;
}
.acai-card h3 {
	font-family: 'Space Grotesk', sans-serif; font-weight: 600; font-size: 20px;
	letter-spacing: -0.01em; margin: 0 0 5px;
}
.acai-card-kicker {
	font-size: 12.5px; font-weight: 600; letter-spacing: 0.04em;
	text-transform: uppercase; color: var(--accent); margin-bottom: 12px;
}
.acai-card p {
	font-size: 14.5px; line-height: 1.62; color: #565B68;
	margin: 0 0 20px; flex: 1; text-wrap: pretty;
}
.acai-card-links {
	display: flex; align-items: center; gap: 18px;
	padding-top: 4px; border-top: 1px solid #EEF0F3; margin-top: 4px;
}
.acai-card-links a {
	display: inline-flex; align-items: center; gap: 6px;
	font-size: 14px; font-weight: 600; padding-top: 16px;
	transition: gap .15s, color .15s;
}
.acai-card-links a.acai-link-primary { color: var(--accent); }
.acai-card-links a.acai-link-primary:hover { gap: 9px; }
.acai-card-links a.acai-link-muted { color: #6B6F7B; }
.acai-card-links a.acai-link-muted:hover { color: var(--accent); }

/* About / Connect */
.acai-about {
	margin-top: 28px; background: #15161B; border-radius: 22px;
	padding: 44px 40px; color: #EDEEF1;
	display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
	gap: 36px; align-items: center;
}
.acai-about h2 {
	font-family: 'Space Grotesk', sans-serif; font-weight: 600;
	font-size: clamp(22px, 3vw, 28px); letter-spacing: -0.02em; margin: 0 0 12px;
}
.acai-about p { font-size: 15px; line-height: 1.65; color: #A6A9B4; margin: 0; text-wrap: pretty; }
.acai-links { display: flex; flex-direction: column; gap: 10px; }
.acai-links a {
	display: flex; align-items: center; gap: 12px;
	padding: 14px 16px; border-radius: 12px;
	background: rgba(255,255,255,0.05);
	border: 1px solid rgba(255,255,255,0.09);
	color: #EDEEF1;
	transition: background .15s, border-color .15s;
}
.acai-links a:hover { background: rgba(255,255,255,0.1); border-color: var(--accent); }
.acai-links a .acai-link-label { flex: 1; font-size: 14.5px; font-weight: 500; }
.acai-links a .acai-link-kind { color: #6E7280; font-size: 13px; }

.acai-footer { text-align: center; padding-top: 30px; font-size: 13px; color: #9094A0; }
</style>
		<?php
	}

	private function print_markup(): void {
		?>
<div class="acai-dashboard">
	<div class="acai-shell">

		<header class="acai-header">
			<div class="acai-brand">
				<div class="acai-brand-mark">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19 12 4l7 15"/><path d="M8.2 13.5h7.6"/></svg>
				</div>
				<div class="acai-brand-text">
					<span class="acai-brand-name">AcrossAI</span>
					<span class="acai-brand-tag"><?php esc_html_e( 'WordPress AI Suite', 'acrossai' ); ?></span>
				</div>
			</div>
			<nav class="acai-social">
				<a href="https://github.com/acrossai-co/" target="_blank" rel="noopener" aria-label="GitHub">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.6.5.5 5.6.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.6 18 4.9 18 4.9c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.6 18.4.5 12 .5z"/></svg>
				</a>
				<a href="https://www.youtube.com/@acrosswp" target="_blank" rel="noopener" aria-label="YouTube">
					<svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>
				</a>
				<a href="https://www.linkedin.com/in/raftaar1191/" target="_blank" rel="noopener" aria-label="LinkedIn">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33 0-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
				</a>
			</nav>
		</header>

		<section class="acai-hero">
			<div aria-hidden="true" class="acai-hero-glow"></div>
			<div class="acai-hero-inner">
				<div class="acai-pill">
					<span class="acai-pill-dot"></span>
					<?php esc_html_e( 'A modular AI stack for WordPress', 'acrossai' ); ?>
				</div>
				<h1><?php esc_html_e( 'Bring real AI infrastructure to your WordPress site.', 'acrossai' ); ?></h1>
				<p><?php esc_html_e( 'AcrossAI connects your site to language models, exposes governed tools, and orchestrates context through the Model Context Protocol — all from the dashboard, no custom code required. Three focused plugins, one cohesive system.', 'acrossai' ); ?></p>
				<div class="acai-chip-row">
					<span class="acai-chip"><?php esc_html_e( 'Govern AI abilities', 'acrossai' ); ?></span>
					<span class="acai-chip"><?php esc_html_e( 'Wire up MCP servers', 'acrossai' ); ?></span>
					<span class="acai-chip"><?php esc_html_e( 'Connect any model', 'acrossai' ); ?></span>
				</div>
			</div>
		</section>

		<section>
			<div class="acai-section-head">
				<h2><?php esc_html_e( 'The Suite', 'acrossai' ); ?></h2>
				<span class="acai-section-meta"><?php esc_html_e( '3 plugins · open source', 'acrossai' ); ?></span>
			</div>

			<div class="acai-grid">

				<article class="acai-card">
					<div class="acai-card-icon">
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4 13.5h6l-1 8.5L20 10.5h-6l1-8.5z"/></svg>
					</div>
					<h3><?php esc_html_e( 'Abilities Manager', 'acrossai' ); ?></h3>
					<div class="acai-card-kicker"><?php esc_html_e( 'Tools & permissions', 'acrossai' ); ?></div>
					<p><?php esc_html_e( 'Define exactly what your AI is allowed to do. Turn WordPress functions into governed, permission-aware abilities your models can call — with clear control over scope and safety.', 'acrossai' ); ?></p>
					<div class="acai-card-links">
						<a class="acai-link-primary" href="https://wordpress.org/plugins/acrossai-abilities-manager/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM3.6 12a8.4 8.4 0 0 1 .73-3.42l4.02 11A8.4 8.4 0 0 1 3.6 12zm8.4 8.4c-.82 0-1.62-.12-2.37-.34l2.52-7.32 2.58 7.07.06.13a8.37 8.37 0 0 1-2.79.46zm1.16-12.34c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1-.82 0-2.2-.1-2.2-.1-.46-.03-.51.66-.06.69 0 0 .43.05.88.08l1.3 3.55-1.82 5.46-3.03-9.01c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1l-.53-.01A8.4 8.4 0 0 1 16.9 4.92c-.03 0-.06-.01-.1-.01-.82 0-1.4.71-1.4 1.48 0 .69.4 1.27.82 1.96.32.56.69 1.27.69 2.3 0 .72-.27 1.55-.64 2.7l-.84 2.8-3.03-9.02zm6.51 1.74a8.4 8.4 0 0 1-3.18 11.3l2.58-7.46c.48-1.2.64-2.17.64-3.02 0-.31-.02-.6-.06-.86z"/></svg>
							WordPress.org
						</a>
						<a class="acai-link-muted" href="https://github.com/acrossai-co/acrossai-core-abilities/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.6.5.5 5.6.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.6 18 4.9 18 4.9c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.6 18.4.5 12 .5z"/></svg>
							GitHub
						</a>
					</div>
				</article>

				<article class="acai-card">
					<div class="acai-card-icon">
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2.4"/><circle cx="5" cy="18.5" r="2.4"/><circle cx="19" cy="18.5" r="2.4"/><path d="M10.7 7.1 6 16.2M13.3 7.1 18 16.2M7.4 18.5h9.2"/></svg>
					</div>
					<h3><?php esc_html_e( 'MCP Manager', 'acrossai' ); ?></h3>
					<div class="acai-card-kicker"><?php esc_html_e( 'Context & connections', 'acrossai' ); ?></div>
					<p><?php esc_html_e( 'Connect WordPress to Model Context Protocol servers. Discover, authorize and manage MCP connections so your AI can safely reach external tools, data and services with full oversight.', 'acrossai' ); ?></p>
					<div class="acai-card-links">
						<a class="acai-link-primary" href="https://wordpress.org/plugins/acrossai-mcp-manager/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM3.6 12a8.4 8.4 0 0 1 .73-3.42l4.02 11A8.4 8.4 0 0 1 3.6 12zm8.4 8.4c-.82 0-1.62-.12-2.37-.34l2.52-7.32 2.58 7.07.06.13a8.37 8.37 0 0 1-2.79.46zm1.16-12.34c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1-.82 0-2.2-.1-2.2-.1-.46-.03-.51.66-.06.69 0 0 .43.05.88.08l1.3 3.55-1.82 5.46-3.03-9.01c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1l-.53-.01A8.4 8.4 0 0 1 16.9 4.92c-.03 0-.06-.01-.1-.01-.82 0-1.4.71-1.4 1.48 0 .69.4 1.27.82 1.96.32.56.69 1.27.69 2.3 0 .72-.27 1.55-.64 2.7l-.84 2.8-3.03-9.02zm6.51 1.74a8.4 8.4 0 0 1-3.18 11.3l2.58-7.46c.48-1.2.64-2.17.64-3.02 0-.31-.02-.6-.06-.86z"/></svg>
							WordPress.org
						</a>
						<a class="acai-link-muted" href="https://github.com/acrossai-co/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.6.5.5 5.6.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.6 18 4.9 18 4.9c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.6 18.4.5 12 .5z"/></svg>
							GitHub
						</a>
					</div>
				</article>

				<article class="acai-card">
					<div class="acai-card-icon">
						<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 3 7.5 12 12l9-4.5L12 3z"/><path d="M3 12.5 12 17l9-4.5"/><path d="M3 17 12 21.5 21 17"/></svg>
					</div>
					<h3><?php esc_html_e( 'Model Manager', 'acrossai' ); ?></h3>
					<div class="acai-card-kicker"><?php esc_html_e( 'Providers & keys', 'acrossai' ); ?></div>
					<p><?php esc_html_e( 'Register AI providers in one place. Store API keys for OpenAI, Anthropic, Google and more, set defaults, and route requests across models — so every AcrossAI plugin shares the same brain.', 'acrossai' ); ?></p>
					<div class="acai-card-links">
						<a class="acai-link-primary" href="https://wordpress.org/plugins/acrossai-model-manager/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM3.6 12a8.4 8.4 0 0 1 .73-3.42l4.02 11A8.4 8.4 0 0 1 3.6 12zm8.4 8.4c-.82 0-1.62-.12-2.37-.34l2.52-7.32 2.58 7.07.06.13a8.37 8.37 0 0 1-2.79.46zm1.16-12.34c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1-.82 0-2.2-.1-2.2-.1-.46-.03-.51.66-.06.69 0 0 .43.05.88.08l1.3 3.55-1.82 5.46-3.03-9.01c.5-.03.96-.08.96-.08.45-.06.4-.72-.05-.69 0 0-1.36.1-2.23.1l-.53-.01A8.4 8.4 0 0 1 16.9 4.92c-.03 0-.06-.01-.1-.01-.82 0-1.4.71-1.4 1.48 0 .69.4 1.27.82 1.96.32.56.69 1.27.69 2.3 0 .72-.27 1.55-.64 2.7l-.84 2.8-3.03-9.02zm6.51 1.74a8.4 8.4 0 0 1-3.18 11.3l2.58-7.46c.48-1.2.64-2.17.64-3.02 0-.31-.02-.6-.06-.86z"/></svg>
							WordPress.org
						</a>
						<a class="acai-link-muted" href="https://github.com/acrossai-co/" target="_blank" rel="noopener">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.6.5.5 5.6.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.6 18 4.9 18 4.9c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.6 18.4.5 12 .5z"/></svg>
							GitHub
						</a>
					</div>
				</article>

			</div>
		</section>

		<section class="acai-about">
			<div>
				<h2><?php esc_html_e( 'Built for builders who want AI on their own terms.', 'acrossai' ); ?></h2>
				<p><?php esc_html_e( 'AcrossAI is an open-source toolkit from the AcrossWP team. Everything is modular, transparent, and yours to extend — pick the plugins you need and ship AI features with confidence.', 'acrossai' ); ?></p>
			</div>
			<div class="acai-links">
				<a href="https://www.acrossai.co/" target="_blank" rel="noopener">
					<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/></svg>
					<span class="acai-link-label">acrossai.co</span>
					<span class="acai-link-kind"><?php esc_html_e( 'Website', 'acrossai' ); ?></span>
				</a>
				<a href="https://github.com/acrossai-co/" target="_blank" rel="noopener">
					<svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .5C5.6.5.5 5.6.5 12c0 5.1 3.3 9.4 7.9 10.9.6.1.8-.3.8-.6v-2c-3.2.7-3.9-1.5-3.9-1.5-.5-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 .1-.8.4-1.3.7-1.6-2.6-.3-5.3-1.3-5.3-5.8 0-1.3.5-2.3 1.2-3.1-.1-.3-.5-1.5.1-3.1 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0C17 4.6 18 4.9 18 4.9c.6 1.6.2 2.8.1 3.1.8.8 1.2 1.8 1.2 3.1 0 4.5-2.7 5.5-5.3 5.8.4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6 4.6-1.5 7.9-5.8 7.9-10.9C23.5 5.6 18.4.5 12 .5z"/></svg>
					<span class="acai-link-label">github.com/acrossai-co</span>
					<span class="acai-link-kind"><?php esc_html_e( 'Code', 'acrossai' ); ?></span>
				</a>
				<a href="https://www.youtube.com/@acrosswp" target="_blank" rel="noopener">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z"/></svg>
					<span class="acai-link-label">@acrosswp</span>
					<span class="acai-link-kind"><?php esc_html_e( 'YouTube', 'acrossai' ); ?></span>
				</a>
				<a href="https://www.linkedin.com/in/raftaar1191/" target="_blank" rel="noopener">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33 0-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
					<span class="acai-link-label">linkedin.com/in/raftaar1191</span>
					<span class="acai-link-kind"><?php esc_html_e( 'LinkedIn', 'acrossai' ); ?></span>
				</a>
				<a href="mailto:deepak@acrossai.co">
					<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/></svg>
					<span class="acai-link-label">deepak@acrossai.co</span>
					<span class="acai-link-kind"><?php esc_html_e( 'Email', 'acrossai' ); ?></span>
				</a>
			</div>
		</section>

		<footer class="acai-footer">
			<?php esc_html_e( '© AcrossAI · A WordPress AI suite by the AcrossWP team', 'acrossai' ); ?>
		</footer>

	</div>
</div>
		<?php
	}
}
