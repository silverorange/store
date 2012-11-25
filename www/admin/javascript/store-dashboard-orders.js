/*
Written in JQuery because the flot charts are built on JQuery
*/
function StoreDashboardOrders(id, data) {
	$(document).ready(function() {
		function toDigits(num) {
			return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
		}

		function dollarFormat(v, axis) {
			return '$' + toDigits(v.toFixed(axis.tickDecimals));
		}

		function showTooltip(x, y, contents) {
			$('<div id="tooltip">' + contents + '</div>').css({
				position: 'absolute',
				display: 'none',
				top: y - 25,
				left: x + 5,
				border: '1px solid #f0dca1',
				padding: '2px',
				'background-color': '#fffbc9',
				color: '#947140',
			}).appendTo("body").fadeIn(200);
		}

		var chart = $.plot($("#" + id), data, {
			yaxes: [{
				alignTicksWithAxis: 1,
				position: 'left',
				tickFormatter: dollarFormat,
				font: {
					size: 11,
					family: 'arial',
				}
				}],
			xaxes: [{
				font: {
					size: 11,
					family: 'arial',
				},
				ticks: [[0, "Jan"], [30, "Feb"], [59, "Mar"], [90, 'Apr'], [120, 'May'],
					[151, 'Jun'], [181, 'Jul'], [211, 'Aug'], [242, 'Sep'], [272, 'Oct'],
					[303, 'Nov'], [334, 'Dec']]
				}],
			grid: {
				borderWidth: 0,
				hoverable: true,
			},
			series: {
				points: { show: true },
				lines: { show: true }
			}
			});

		$(window).resize(function() {
			chart.resize();
			chart.setupGrid();
			chart.draw();
		});

		var current_point = null;
		$("#" + id).bind("plothover", function (event, pos, item) {
			if (item) {
				if (current_point === null || (current_point[0] != item.datapoint[0]
					&& current_point[1] != item.datapoint[1])) {

					$("#tooltip").remove();
					showTooltip(item.pageX, item.pageY,	'$' + toDigits(item.datapoint[1].toFixed()));
					current_point = item.datapoint;
				}
			} else {
				current_point = null;
				$("#tooltip").remove();
			}
		});
	});
};
