/*
Written in JQuery because the flot charts are built on JQuery
*/
function StoreOrderChart(id, data, options) {
  var options = options || {};
  var showY = typeof options.showY === 'boolean' ? options.showY : true;
  $(document).ready(function () {
    function toDigits(num) {
      return num.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1,');
    }

    function dollarFormat(v, axis) {
      return '$' + toDigits(v.toFixed(axis.tickDecimals));
    }

    function showTooltip(x, y, contents) {
      $('<div id="tooltip">' + contents + '</div>')
        .addClass('store-order-chart-tooltip')
        .css({
          top: y - 25,
          left: x + 5
        })
        .appendTo('body')
        .fadeIn(200);
    }

    var chart = $.plot($('#' + id), data, {
      yaxes: [
        {
          show: showY,
          alignTicksWithAxis: 1,
          position: 'left',
          tickFormatter: dollarFormat,
          font: {
            size: 11,
            family: 'arial'
          }
        }
      ],
      xaxes: [
        {
          font: {
            size: 11,
            family: 'arial'
          },
          ticks: [
            [0, 'Jan'],
            [30, 'Feb'],
            [59, 'Mar'],
            [90, 'Apr'],
            [120, 'May'],
            [151, 'Jun'],
            [181, 'Jul'],
            [211, 'Aug'],
            [242, 'Sep'],
            [272, 'Oct'],
            [303, 'Nov'],
            [334, 'Dec']
          ]
        }
      ],
      grid: {
        borderWidth: 0,
        hoverable: true
      },
      series: {
        points: { show: true },
        lines: { show: true }
      },
      legend: {
        container: '#' + id + '_legend',
        noColumns: 2
      },
      colors: ['#aed581', '#689f38']
    });

    $('#' + id + '_legend .legendColorBox > div').css({
      padding: '0',
      border: '0',
      borderRadius: '2px',
      overflow: 'hidden'
    });
    $('#' + id + '_legend .legendColorBox > div > div').css({
      borderWidth: '8px'
    });

    $(window).resize(function () {
      chart.resize();
      chart.setupGrid();
      chart.draw();
    });

    if (showY) {
      var current_point = null;
      $('#' + id).bind('plothover', function (event, pos, item) {
        if (item) {
          if (
            current_point === null ||
            (current_point[0] != item.datapoint[0] &&
              current_point[1] != item.datapoint[1])
          ) {
            $('#tooltip').remove();
            showTooltip(
              item.pageX,
              item.pageY,
              '$' + toDigits(item.datapoint[1].toFixed())
            );
            current_point = item.datapoint;
          }
        } else {
          current_point = null;
          $('#tooltip').remove();
        }
      });
    }
  });
}
