$.fn.radialTree = function(jsonTree) {
    var diameter = 1000;
    var tree = d3.layout.tree()
        .size([360, diameter / 2 - 50])
        .separation(function(a, b) { return (a.parent == b.parent ? 1 : 2) / a.depth; });
    var diagonal = d3.svg.diagonal.radial()
        .source(function(d) { return d.source.depth == 0 ? { x: d.target.x, y: d.source.y } : d.source; })
        .projection(function(d) { return [d.y, d.x / 180 * Math.PI]; });
    var svg = d3.select(".radial-tree").append("svg")
        .attr("width", diameter)
        .attr("height", diameter)
        .append("g")
        .attr("transform", "translate(" + diameter / 2 + "," + diameter / 2 + ")");
    var nodes = tree.nodes(jsonTree),
        links = tree.links(nodes);
    var link = svg.selectAll(".link")
        .data(links)
        .enter().append("path")
        .attr("class", "link")
        .attr("d", diagonal);
    var node = svg.selectAll(".node")
        .data(nodes)
        .enter().append("g")
        .attr("class", "node")
        .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
    node.append("circle")
        .attr("r", 4.5);
    node.append("text")
        .attr("dy", ".31em")
        .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
        .attr("transform", function(d) { return d.x < 180 ? "translate(8)" : "rotate(180)translate(-8)"; })
        .append("a")
            .attr("href", function(d) { return Routing.generate('app_collection_show', {id : d.id }) })
            .html(function(d) { return d.name.length > 21 ? d.name.substring(0, 18).trim() + '...' : d.name; });
    d3.select(self.frameElement).style("height", diameter - 150 + "px");
};