
import { writeFileSync, readFileSync } from "fs";

function run() {
    const chunk = "CREATE TABLE `wp_posts` (\n  `id` int(11) NOT NULL\n);\n";
    const largeStr = chunk.repeat(1000000);
    console.log("String created: " + (largeStr.length / 1024 / 1024).toFixed(2) + " MB");
    
    console.time("Replace");
    const result = largeStr.replace(/`wp_/g, "`vh_");
    console.timeEnd("Replace");
    
    console.log("Success!");
}
run();

