/* assets/js/admin.js */
jQuery(document).ready(function ($) {
  // Chart initialization
  var canvas = document.getElementById("login-sentinel-chart");
  if (canvas) {
    var ctx = canvas.getContext("2d");
    if (typeof loginSentinelChartData === "undefined") {
      console.error("Chart data not defined.");
    } else {
      var loginChart = new Chart(ctx, {
        type: "line",
        data: loginSentinelChartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
            },
          },
        },
      });
    }
  }

  // View More for login attempts table
  $("#view-more-attempts").on("click", function (e) {
    e.preventDefault();
    var offset = parseInt($("#attempts-offset").val());
    var loaderPlaceholder =
      '<tr id="attempts-loader" class="animate-pulse"><td colspan="5" class="py-2 text-center text-gray-500">Loading...</td></tr>';
    $("#attempts-tbody").append(loaderPlaceholder);
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "login_sentinel_load_more_logs",
        type: "attempts",
        offset: offset,
        nonce: loginSentinelLoadMoreNonce,
      },
      success: function (response) {
        $("#attempts-loader").remove();
        if (response) {
          $("#attempts-tbody").append(response);
          $("#attempts-offset").val(offset + 10);
        }
      },
      error: function (error) {
        console.error("Error loading more attempts:", error);
        $("#attempts-loader").remove();
      },
    });
  });

  // View More for IP blocks table
  $("#view-more-blocks").on("click", function (e) {
    e.preventDefault();
    var offset = parseInt($("#blocks-offset").val());
    var loaderPlaceholder =
      '<tr id="blocks-loader" class="animate-pulse"><td colspan="4" class="py-2 text-center text-gray-500">Loading...</td></tr>';
    $("#blocks-tbody").append(loaderPlaceholder);
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "login_sentinel_load_more_logs",
        type: "blocks",
        offset: offset,
        nonce: loginSentinelLoadMoreNonce,
      },
      success: function (response) {
        $("#blocks-loader").remove();
        if (response) {
          $("#blocks-tbody").append(response);
          $("#blocks-offset").val(offset + 10);
        }
      },
      error: function (error) {
        console.error("Error loading more blocks:", error);
        $("#blocks-loader").remove();
      },
    });
  });

  // Handle "Send Metrics Email" form submission on settings page.
  $("#send-email-form").on("submit", function (e) {
    e.preventDefault();
    var frequency = $("#email_frequency_manual").val();
    $("#send-email-message").html(
      '<div class="animate-pulse text-gray-500">Sending email...</div>'
    );
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "login_sentinel_send_email_now",
        frequency: frequency,
        nonce: loginSentinelSendEmailNowNonce,
      },
      success: function (response) {
        if (response.success) {
          $("#send-email-message").html(
            '<div class="text-green-600">Email sent successfully.</div>'
          );
        } else {
          $("#send-email-message").html(
            '<div class="text-red-600">Failed to send email.</div>'
          );
        }
      },
      error: function (error) {
        console.error("Error sending email:", error);
        $("#send-email-message").html(
          '<div class="text-red-600">Error sending email.</div>'
        );
      },
    });
  });

  // Handle "Apply Filter" for historical metrics.
  $("#apply-date-filter").on("click", function (e) {
    e.preventDefault();
    var startDate = $("#start_date").val();
    var endDate = $("#end_date").val();
    if (!startDate || !endDate) {
      $("#date-filter-message").html(
        '<div class="text-red-600">Please select both start and end dates.</div>'
      );
      return;
    }
    $("#date-filter-message").html(
      '<div class="animate-pulse text-gray-500">Loading historical data...</div>'
    );
    $.ajax({
      url: ajaxurl,
      method: "POST",
      data: {
        action: "login_sentinel_get_historical_metrics",
        start_date: startDate,
        end_date: endDate,
        nonce: loginSentinelGetHistoricalMetricsNonce,
      },
      success: function (response) {
        $("#date-filter-message").empty();
        if (response.success && response.data) {
          var agg = response.data.aggregated;
          if (
            Number(agg.total_success) === 0 &&
            Number(agg.total_failed) === 0 &&
            Number(agg.total_blocked) === 0 &&
            Number(agg.total_login_attempts) === 0 &&
            Number(agg.total_ip_blocks) === 0
          ) {
            $("#date-filter-message").html(
              '<div class="text-red-600">No data for these dates.</div>'
            );
          } else {
            $("#card-success").text(Number(agg.total_success).toLocaleString());
            $("#card-failed").text(Number(agg.total_failed).toLocaleString());
            $("#card-blocked").text(Number(agg.total_blocked).toLocaleString());
            $("#card-total").text(
              Number(agg.total_login_attempts).toLocaleString()
            );
            $("#card-active").text(
              Number(agg.total_ip_blocks).toLocaleString()
            );

            $("#date-filter-message").html(
              '<div class="text-green-600">Showing metrics from ' +
                startDate +
                " to " +
                endDate +
                ".</div>"
            );
          }

          loginSentinelChartData.labels = response.data.chart.labels;
          loginSentinelChartData.datasets[0].data =
            response.data.chart.datasets[0].data;
          loginSentinelChartData.datasets[1].data =
            response.data.chart.datasets[1].data;
          loginSentinelChartData.datasets[2].data =
            response.data.chart.datasets[2].data;
          loginSentinelChartData.datasets[3].data =
            response.data.chart.datasets[3].data;
          loginSentinelChartData.datasets[4].data =
            response.data.chart.datasets[4].data;

          if (typeof loginChart !== "undefined") {
            loginChart.destroy();
          }
          var canvas = document.getElementById("login-sentinel-chart");
          if (canvas) {
            var ctx = canvas.getContext("2d");
            loginChart = new Chart(ctx, {
              type: "line",
              data: loginSentinelChartData,
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: {
                    beginAtZero: true,
                    ticks: {
                      precision: 0,
                    },
                  },
                },
              },
            });
          }
        } else {
          $("#date-filter-message").html(
            '<div class="text-red-600">Failed to load historical data.</div>'
          );
        }
      },
      error: function (error) {
        console.error("Error fetching historical metrics:", error);
        $("#date-filter-message").html(
          '<div class="text-red-600">Error loading historical data.</div>'
        );
      },
    });
  });

  // Handle "Reset to Live Data" button.
  $("#reset-date-filter").on("click", function (e) {
    e.preventDefault();
    // Reload the page to show live data.
    location.reload();
  });
});
