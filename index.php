<?php
$hostname = 'localhost';
$username = '';
$password = '';

try {
    $dbh = new PDO("mysql:host=$hostname;dbname=temps", $username, $password);

   /*** The SQL SELECT statement ***/
    $sth = $dbh->prepare("
       SELECT  `datetime`, `temp` FROM  `temp`
    ");
    $sth->execute();

    /* Fetch all of the remaining rows in the result set */
    $result = $sth->fetchAll(PDO::FETCH_ASSOC);

    /*** close the database connection ***/
    $dbh = null;

}
catch(PDOException $e) {
	echo $e->getMessage();
}

$json_data = json_encode($result);
?>

<!DOCTYPE html>
<meta charset="utf-8">
<title>Temperature Log</title>

<style>
body {
  font: 10px sans-serif;
}

.axis path,
.axis line {
  fill: none;
  stroke: #000;
  shape-rendering: crispEdges;
}

/*
.x.axis path {
  display: none;
}
*/

.line {
  stroke: steelblue;
  fill: none;
  stroke-width: 0.75px;
}

.line.line0 {
  stroke: steelblue;
  stroke-width: 0.75px;
}

.line.line1 {
  stroke: indianred;
}

.overlay {
  fill: none;
  pointer-events: all;
}

.focus circle {
  fill: none;
}

.focus circle.y0 {
  stroke: blue;
}

.focus circle.y1 {
  stroke: red;
}

.focus line {
  stroke: purple;
  shape-rendering: crispEdges;
}

.focus line.y0 {
  stroke: steelblue;
  stroke-dasharray: 3 3;
  opacity: .5;
}

.focus line.y1 {
  stroke: indianred;
  stroke-dasharray: 3 3;
  opacity: .5;
}

.brush .extent {
  stroke: #fff;
  fill-opacity: .125;
  shape-rendering: crispEdges;
}
</style>

<body>
<script type="text/javascript" src="http://d3js.org/d3.v3.min.js"></script>

<script>
var graphWidth = window.innerWidth;

var main_margin = {top: 20, right: 80, bottom: 100, left: 40},
    mini_margin = {top: 430, right: 80, bottom: 20, left: 40},
    main_width = graphWidth - main_margin.left - main_margin.right,
    main_height = 500 - main_margin.top - main_margin.bottom,
    mini_height = 500 - mini_margin.top - mini_margin.bottom;

var formatDate = d3.time.format("%Y-%m-%d %H:%M:%S"),
    parseDate = formatDate.parse,
    bisectDate = d3.bisector(function(d) { return d.datetime; }).left,
    formatOutput0 = function(d) { return d.temp; };
    
<?php echo "data=".$json_data.";" ?>

data.forEach(function(d){
 d.datetime = parseDate(d.datetime);
 d.temp = +d.temp;
});

var main_x = d3.time.scale().range([0, main_width]),
    mini_x = d3.time.scale().range([0, main_width]),
    main_y = d3.scale.linear().range([main_height, 0]),
    mini_y = d3.scale.linear().range([mini_height, 0]);

var main_xAxis = d3.svg.axis()
    .scale(main_x)
    .tickFormat(d3.time.format("%b %d %H:%M"))
    .orient("bottom");

var mini_xAxis = d3.svg.axis()
    .scale(mini_x)
	//.ticks(4)
    .tickFormat(d3.time.format("%B %d"))
    .orient("bottom");

var main_yAxisLeft = d3.svg.axis()
    .scale(main_y)
    .orient("left");
    main_yAxisRight = d3.svg.axis()
    .scale(main_y)
    .orient("right");

var brush = d3.svg.brush()
    .x(mini_x)
    .on("brush", brush);

var main_line0 = d3.svg.line()
    .interpolate("linear")
    .x(function(d) { return main_x(d.datetime); })
    .y(function(d) { return main_y(d.temp); });

var mini_line0 = d3.svg.line()
    .x(function(d) { return mini_x(d.datetime); })
    .y(function(d) { return mini_y(d.temp); });

var svg = d3.select("body").append("svg")
    .attr("width", main_width + main_margin.left + main_margin.right)
    .attr("height", main_height + main_margin.top + main_margin.bottom);

svg.append("defs").append("clipPath")
    .attr("id", "clip")
    .append("rect")
    .attr("width", main_width)
    .attr("height", main_height);

var main = svg.append("g")
    .attr("transform", "translate(" + main_margin.left + "," + main_margin.top + ")");

var mini = svg.append("g")
    .attr("transform", "translate(" + mini_margin.left + "," + mini_margin.top + ")");

  main_x.domain([data[0].datetime, data[data.length - 1].datetime]);
  //main_y.domain(d3.extent(data, function(d) { return d.temp - 0.5; }));
  main_y.domain([d3.min(data, function(d) { return d.temp - 0.5; }), d3.max(data, function(d) { return d.temp + 0.5; })]);
  mini_x.domain(main_x.domain());
  mini_y.domain(main_y.domain());

  main.append("path")
      .datum(data)
      .attr("clip-path", "url(#clip)")
      .attr("class", "line line0")
      .attr("d", main_line0);

  main.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + main_height + ")")
      .call(main_xAxis);

  main.append("g")
      .attr("class", "y axis axisLeft")
      .call(main_yAxisLeft)
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", 6)
      .attr("dy", ".71em")
      .style("text-anchor", "end")
      .text("Temperature (C)");

  main.append("g")
      .attr("class", "y axis axisRight")
      .attr("transform", "translate(" + main_width + ", 0)")
      .call(main_yAxisRight)
    .append("text")
      .attr("transform", "rotate(-90)")
      .attr("y", -12)
      .attr("dy", ".71em")
      .style("text-anchor", "end")
      .text("Temperature (C)");

  mini.append("g")
      .attr("class", "x axis")
      .attr("transform", "translate(0," + mini_height + ")")
      .call(mini_xAxis);

  mini.append("path")
      .datum(data)
      .attr("class", "line")
      .attr("d", mini_line0);

  mini.append("g")
      .attr("class", "x brush")
      .call(brush)
    .selectAll("rect")
      .attr("y", -6)
      .attr("height", mini_height + 7);

  var focus = main.append("g")
      .attr("class", "focus")
      .style("display", "none");

  focus.append("line")
      .attr("class", "y")
      .attr("y1", main_y(0) - 6)
      .attr("y2", main_y(0) + 6)

  focus.append("line")
      .attr("class", "x")
      .attr("x1", main_width - 6)
      .attr("x2", main_width + 6);

  focus.append("line")
      .attr("class", "x1")
      .attr("x1", main_width - 6)
      .attr("x2", main_width + 6);

  focus.append("circle")
      .attr("class", "y0")
      .attr("r", 4);

  focus.append("text")
      .attr("class", "y0")
      .attr("dy", "-1em");
/*
  focus.append("circle")
      .attr("class", "y0")
      .attr("r", 4);

  focus.append("text")
      .attr("class", "y1")
      .attr("dy", "-1em");
*/
  main.append("rect")
      .attr("class", "overlay")
      .attr("width", main_width)
      .attr("height", main_height)
      .on("mouseover", function() { focus.style("display", null); })
      .on("mouseout", function() { focus.style("display", "none"); })
      .on("mousemove", mousemove);

  function mousemove() {
    var x0 = main_x.invert(d3.mouse(this)[0]),
        i = bisectDate(data, x0, 1),
        d0 = data[i - 1],
        d1 = data[i],
        d = x0 - d0.datetime > d1.datetime - x0 ? d1 : d0;
    focus.select("circle.y0").attr("transform", "translate(" + main_x(d.datetime) + "," + main_y(d.temp) + ")");
    focus.select("text.y0").attr("transform", "translate(" + main_x(d.datetime) + "," + main_y(d.temp) + ")").text(formatOutput0(d));
    //focus.select(".x").attr("transform", "translate(" + main_x(d.datetime) + ",0)");
    //focus.select(".y0").attr("transform", "translate(" + main_width * -1 + ", " + main_y(d.datetime) + ")").attr("x2", main_width + main_x(d.datetime));
  }


function brush() {
  main_x.domain(brush.empty() ? mini_x.domain() : brush.extent());
  main.select(".line0").attr("d", main_line0);
  //main.select(".line1").attr("d", main_line1);
  main.select(".x.axis").call(main_xAxis);
}
</script>
