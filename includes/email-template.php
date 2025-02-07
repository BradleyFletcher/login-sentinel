<?php

/**
 * Email Template for Login Sentinel Metrics Email
 *
 * Generates the HTML email body for the Login Sentinel metrics email.
 *
 * @package Login_Sentinel
 */

/**
 * Generates the HTML email template.
 *
 * @param string $subject     The email subject.
 * @param string $start_time  The start time for the metrics (Y-m-d H:i:s).
 * @param array  $metrics     An associative array with keys: success, failed, blocked, total_login_attempts, active.
 * @param string $footer      Optional. Footer text. Defaults to a standard message.
 * @return string             The complete HTML email content.
 */
function login_sentinel_get_email_template($subject, $start_time, $metrics, $footer = '')
{
  if (empty($footer)) {
    $footer = 'You received this email because you subscribed to Login Sentinel notifications. To stop receiving these emails, please disable email notifications in your Login Sentinel settings. Login Sentinel is a product of Unifyr.io and respects your privacy.';
  }
  // Format the start time to a more readable format.
  $readable_date = date("F j, Y, g:i a", strtotime($start_time));
  ob_start();
?>
  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($subject); ?></title>
    <style type="text/css">
      body {
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
      }

      .email-container {
        max-width: 600px;
        margin: 0 auto;
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
      }

      .header {
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 24px;
        font-weight: bold;
        color: #2F855A;
        padding-bottom: 10px;
      }

      .content {
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 16px;
        color: #4A5568;
        padding-bottom: 20px;
      }

      .metrics-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
      }

      .metrics-table th,
      .metrics-table td {
        padding: 8px;
        border: 1px solid #CBD5E0;
        text-align: left;
        font-family: Arial, sans-serif;
        font-size: 16px;
      }

      .metrics-table th {
        background-color: #EDF2F7;
      }

      .footer {
        text-align: center;
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #718096;
        margin-top: 20px;
      }

      @media screen and (max-width:600px) {
        .email-container {
          width: 100% !important;
        }
      }

      /* Dark mode support */
      @media (prefers-color-scheme: dark) {
        body {
          background-color: #333333 !important;
          color: #ffffff !important;
        }

        .email-container {
          background-color: #444444 !important;
        }

        .header {
          color: #ffffff !important;
        }

        .content {
          color: #ffffff !important;
        }

        .metrics-table th,
        .metrics-table td {
          border: 1px solid #666666 !important;
        }

        .footer {
          color: #a0aec0 !important;
        }
      }
    </style>
  </head>

  <body>
    <div class="email-container">
      <div style="text-align:center; margin-bottom:20px;">
        <img src="<?php echo esc_url(LOGIN_SENTINEL_PLUGIN_URL . 'assets/images/lslogo.png'); ?>" alt="Login Sentinel Logo" style="display:block; margin:0 auto; max-width:150px;" />
      </div>
      <div class="header"><?php echo esc_html($subject); ?></div>
      <div class="content">Metrics for the period since <?php echo esc_html($readable_date); ?></div>
      <table class="metrics-table">
        <tr>
          <th>Metric</th>
          <th>Value</th>
        </tr>
        <tr>
          <td>Successful Logins</td>
          <td style="color:#16A34A;"><?php echo number_format($metrics['success']); ?></td>
        </tr>
        <tr style="background-color:#EDF2F7;">
          <td>Failed Logins</td>
          <td style="color:#DC2626;"><?php echo number_format($metrics['failed']); ?></td>
        </tr>
        <tr>
          <td>Blocked Logins</td>
          <td style="color:#D97706;"><?php echo number_format($metrics['blocked']); ?></td>
        </tr>
        <tr style="background-color:#EDF2F7;">
          <td>Total Login Attempts</td>
          <td style="color:#3B82F6;"><?php echo number_format($metrics['total_login_attempts']); ?></td>
        </tr>
        <tr>
          <td>Active IP Blocks</td>
          <td style="color:#8B5CF6;"><?php echo number_format($metrics['active']); ?></td>
        </tr>
      </table>
      <div class="footer"><?php echo esc_html($footer); ?></div>
    </div>
  </body>

  </html>
<?php
  return ob_get_clean();
}
