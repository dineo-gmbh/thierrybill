<?php
/**
 * Weekly Summary Report email template.
 *
 * @package CF7Apps
 *
 * @var string $user_name
 * @var string $week_range
 * @var int    $total_forms
 * @var int    $total_entries
 * @var array  $by_form
 * @var string $logo_url
 * @var string $page_icon_url
 * @var string $spam_icon_url
 * @var string $dashboard_url
 * @var bool   $entries_available
 * @var int    $total_spam
 * @var bool   $show_spam
 * @var string $fonts_url
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'cf7apps_weekly_summary_email_image_src' ) ) {
	/**
	 * Escape an email image source, preserving cid: inline references.
	 *
	 * @param string $src Image source.
	 * @return string
	 */
	function cf7apps_weekly_summary_email_image_src( $src ) {
		$src = (string) $src;

		if ( 0 === strpos( $src, 'cid:' ) ) {
			return esc_attr( $src );
		}

		return esc_url( $src );
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="color-scheme" content="light only">
	<meta name="supported-color-schemes" content="light">
	<title><?php esc_html_e( 'Weekly Forms Summary', 'cf7apps' ); ?></title>
	<style type="text/css">
		:root {
			color-scheme: light only;
			supported-color-schemes: light;
		}
		<?php if ( ! empty( $fonts_url ) ) : ?>
		@font-face {
			font-family: 'Degular Demo';
			font-style: normal;
			font-weight: 400;
			font-display: swap;
			src: url('<?php echo esc_url( $fonts_url . 'Degular-Regular.otf' ); ?>') format('opentype');
		}
		@font-face {
			font-family: 'Degular Demo';
			font-style: normal;
			font-weight: 500;
			font-display: swap;
			src: url('<?php echo esc_url( $fonts_url . 'Degular-Medium.otf' ); ?>') format('opentype');
		}
		@font-face {
			font-family: 'Degular Demo';
			font-style: normal;
			font-weight: 600;
			font-display: swap;
			src: url('<?php echo esc_url( $fonts_url . 'Degular-Semibold.otf' ); ?>') format('opentype');
		}
		@font-face {
			font-family: 'Degular Demo';
			font-style: normal;
			font-weight: 700;
			font-display: swap;
			src: url('<?php echo esc_url( $fonts_url . 'Degular-Bold.otf' ); ?>') format('opentype');
		}
		<?php endif; ?>
		body, table, td, p, h1, h2, h3, span, a, strong {
			font-family: 'Degular Demo', 'Degular', 'degular', Arial, Helvetica, sans-serif !important;
		}
		body {
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}
		table {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}
		img {
			border: 0;
			height: auto;
			line-height: 100%;
			outline: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
		}
		.email-card,
		.email-card td,
		.email-card p,
		.email-card h1,
		.email-card strong {
			background-color: #ffffff !important;
			color: #21222f !important;
		}
		.email-forms-header {
			background-color: #f4f4f5 !important;
			color: #21222f !important;
		}
		.email-stat-label-short {
			display: none;
		}
		@media only screen and (max-width: 600px) {
			.email-outer-padding {
				padding: 16px 8px !important;
			}
			.email-section-padding {
				padding-left: 16px !important;
				padding-right: 16px !important;
			}
			.email-logo-padding {
				padding: 28px 16px 16px !important;
			}
			.email-stat-card {
				padding: 14px !important;
			}
			.email-stat-icon {
				width: 32px !important;
				height: 32px !important;
			}
			.email-stat-icon-cell {
				width: 42px !important;
				padding-right: 8px !important;
			}
			.email-stat-text {
				font-size: 14px !important;
				line-height: 1.3 !important;
			}
			.email-stat-label-long {
				display: none !important;
			}
			.email-stat-label-short {
				display: inline !important;
			}
			.email-stat-badge {
				font-size: 16px !important;
				padding: 4px 12px !important;
			}
			.email-stat-badge-cell {
				width: 56px !important;
				padding-left: 6px !important;
			}
			.email-footer-padding {
				padding: 0 16px 24px !important;
			}
		}
	</style>
</head>
<body style="margin:0;padding:0;background-color:#f6f7f7;font-family:'Degular Demo','Degular','degular',Arial,Helvetica,sans-serif;color:#21222f;">
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#f6f7f7" style="background-color:#f6f7f7;">
		<tr>
			<td class="email-outer-padding" align="center" style="padding:32px 16px;">
				<table role="presentation" class="email-card" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#ffffff" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:10px;overflow:hidden;">
					<tr>
						<td class="email-logo-padding" style="padding:40px 32px 24px;" align="center">
							<?php if ( ! empty( $logo_url ) ) : ?>
								<img src="<?php echo cf7apps_weekly_summary_email_image_src( $logo_url ); ?>" alt="<?php esc_attr_e( 'CF7 Apps', 'cf7apps' ); ?>" width="260" style="display:block;width:260px;max-width:100%;height:auto;border:0;">
							<?php else : ?>
								<p style="margin:0;font-size:24px;font-weight:700;color:#0d2149;">CF7 Apps</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td class="email-section-padding" style="padding:0 32px 16px;">
							<h1 class="email-title" style="margin:0 0 24px;font-size:24px;line-height:1.3;font-weight:700;color:#21222f;text-align:center;">
								<?php esc_html_e( 'Weekly Forms Summary', 'cf7apps' ); ?>
							</h1>
							<p style="margin:0 0 16px;font-size:14px;line-height:1.5;color:#21222f;">
								<?php esc_html_e( 'Hi there,', 'cf7apps' ); ?>
							</p>
							<p style="margin:0 0 24px;font-size:14px;line-height:1.5;color:#21222f;">
								<?php esc_html_e( 'Here’s a quick look at how your forms performed over the past week.', 'cf7apps' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td class="email-section-padding" style="padding:0 32px 20px;">
							<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #eeeeee;border-radius:10px;">
								<tr>
									<td class="email-stat-card" style="padding:20px;">
										<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
											<tr>
												<td style="<?php echo ! empty( $show_spam ) ? 'padding-bottom:20px;' : 'padding-bottom:0;'; ?>">
													<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
														<tr>
															<?php if ( ! empty( $page_icon_url ) ) : ?>
																<td class="email-stat-icon-cell" width="50" valign="top" style="width:50px;padding-right:10px;vertical-align:top;">
																	<img class="email-stat-icon" src="<?php echo cf7apps_weekly_summary_email_image_src( $page_icon_url ); ?>" alt="" width="40" height="40" style="display:block;width:40px;height:40px;border:0;">
																</td>
															<?php endif; ?>
															<td valign="top" class="email-stat-text" style="vertical-align:top;font-size:16px;line-height:1.4;font-weight:700;color:#21222f;">
																<span class="email-stat-label-long"><?php esc_html_e( 'Total entries this week:', 'cf7apps' ); ?></span>
																<span class="email-stat-label-short"><?php esc_html_e( 'Entries this week:', 'cf7apps' ); ?></span>
															</td>
															<td class="email-stat-badge-cell" width="72" valign="top" align="right" style="width:72px;padding-left:8px;vertical-align:top;white-space:nowrap;">
																<span class="email-stat-badge" style="display:inline-block;padding:6px 20px;border:1px solid #208aae;border-radius:6px;font-size:18px;font-weight:700;color:#208aae;white-space:nowrap;">
																	<?php echo esc_html( (string) (int) $total_entries ); ?>
																</span>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<?php if ( ! empty( $show_spam ) ) : ?>
												<tr>
													<td>
														<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
															<tr>
																<?php if ( ! empty( $spam_icon_url ) ) : ?>
																	<td class="email-stat-icon-cell" width="50" valign="top" style="width:50px;padding-right:10px;vertical-align:top;">
																		<img class="email-stat-icon" src="<?php echo cf7apps_weekly_summary_email_image_src( $spam_icon_url ); ?>" alt="" width="40" height="40" style="display:block;width:40px;height:40px;border:0;">
																	</td>
																<?php endif; ?>
																<td valign="top" class="email-stat-text" style="vertical-align:top;font-size:16px;line-height:1.4;font-weight:700;color:#21222f;">
																	<span class="email-stat-label-long"><?php esc_html_e( 'Total Spam entries this week:', 'cf7apps' ); ?></span>
																	<span class="email-stat-label-short"><?php esc_html_e( 'Spam entries this week:', 'cf7apps' ); ?></span>
																</td>
																<td class="email-stat-badge-cell" width="72" valign="top" align="right" style="width:72px;padding-left:8px;vertical-align:top;white-space:nowrap;">
																	<span class="email-stat-badge" style="display:inline-block;padding:6px 20px;border:1px solid #208aae;border-radius:6px;font-size:18px;font-weight:700;color:#208aae;white-space:nowrap;">
																		<?php echo esc_html( (string) (int) $total_spam ); ?>
																	</span>
																</td>
															</tr>
														</table>
													</td>
												</tr>
											<?php endif; ?>
										</table>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td class="email-section-padding" style="padding:0 32px 24px;">
							<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #eeeeee;border-radius:10px;overflow:hidden;border-collapse:separate;border-spacing:0;">
								<tr>
									<td class="email-forms-header" width="75%" style="width:75%;padding:12px 20px;background-color:#f4f4f5;font-size:14px;font-weight:700;color:#21222f;border-top-left-radius:9px;">
										<?php esc_html_e( 'Forms', 'cf7apps' ); ?>
									</td>
									<td class="email-forms-header" width="25%" align="right" style="width:25%;padding:12px 20px;background-color:#f4f4f5;font-size:14px;font-weight:700;color:#21222f;border-top-right-radius:9px;">
										<?php esc_html_e( 'Entries', 'cf7apps' ); ?>
									</td>
								</tr>
								<?php if ( ! empty( $by_form ) ) : ?>
									<?php foreach ( $by_form as $row ) : ?>
										<tr>
											<td class="email-form-name" width="75%" valign="top" style="width:75%;padding:12px 20px;border-top:1px solid #eeeeee;font-size:14px;line-height:1.4;color:#21222f;word-break:break-word;">
												<?php echo esc_html( $row['form_name'] ); ?>
											</td>
											<td class="email-form-count" width="25%" align="right" valign="top" style="width:25%;padding:12px 20px;border-top:1px solid #eeeeee;font-size:14px;line-height:1.4;font-weight:700;color:#208aae;white-space:nowrap;">
												<?php echo esc_html( (string) (int) $row['entry_count'] ); ?>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td class="email-empty-row" colspan="2" style="padding:16px 20px;border-top:1px solid #eeeeee;font-size:14px;color:#717171;">
											<?php
											if ( empty( $entries_available ) ) {
												esc_html_e( 'No entry data is available. Enable the CF7 Entries app to track submissions.', 'cf7apps' );
											} else {
												esc_html_e( 'No submissions were received during this reporting period.', 'cf7apps' );
											}
											?>
										</td>
									</tr>
								<?php endif; ?>
							</table>
						</td>
					</tr>
					<tr>
						<td class="email-footer-padding email-section-padding" style="padding:0 32px 32px;">
							<p style="margin:0 0 24px;font-size:14px;line-height:1.5;color:#21222f;">
								<?php esc_html_e( 'Thanks for using CF7 Apps we’re happy to help you keep track of your submissions!', 'cf7apps' ); ?>
							</p>
							<p style="margin:0;font-size:14px;line-height:1.5;color:#21222f;">
								<?php esc_html_e( 'Warm regards,', 'cf7apps' ); ?><br>
								<strong style="color:#208aae;"><?php esc_html_e( 'CF7 Apps Team', 'cf7apps' ); ?></strong>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
