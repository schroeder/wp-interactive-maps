const fs = require('fs');

// Read the SVG file
const svgContent = fs.readFileSync('karte_bistum.svg', 'utf8');

// Extract all path elements with their IDs and d attributes
const pathRegex = /<path[^>]*>/g;
let match;
const paths = [];

while ((match = pathRegex.exec(svgContent)) !== null) {
    const pathElement = match[0];
    const dMatch = pathElement.match(/d="([^"]*)"/);
    const idMatch = pathElement.match(/id="([^"]*)"/);
    const transformMatch = pathElement.match(/transform="([^"]*)"/);
    
    if (dMatch && idMatch) {
        paths.push({
            id: idMatch[1],
            d: dMatch[1],
            transform: transformMatch ? transformMatch[1] : null
        });
    }
}

// Parse transform matrix
function parseTransform(transformStr) {
    if (!transformStr) return null;
    
    const matrixMatch = transformStr.match(/matrix\(([-\d.]+),([-\d.]+),([-\d.]+),([-\d.]+),([-\d.]+),([-\d.]+)\)/);
    if (matrixMatch) {
        return {
            a: parseFloat(matrixMatch[1]),
            b: parseFloat(matrixMatch[2]),
            c: parseFloat(matrixMatch[3]),
            d: parseFloat(matrixMatch[4]),
            e: parseFloat(matrixMatch[5]),
            f: parseFloat(matrixMatch[6])
        };
    }
    return null;
}

// Apply transform to coordinates
function applyTransform(x, y, matrix) {
    if (!matrix) return [x, y];
    return [
        matrix.a * x + matrix.c * y + matrix.e,
        matrix.b * x + matrix.d * y + matrix.f
    ];
}

// Parse SVG path data and convert to coordinate arrays
function pathToCoordinates(pathData, transform) {
    const matrix = parseTransform(transform);
    const coordinates = [];
    
    // Split path into commands
    const commands = pathData.match(/[MmLlHhVvCcSsQqTtAaZz][^MmLlHhVvCcSsQqTtAaZz]*/g);
    
    let currentX = 0, currentY = 0;
    let startX = 0, startY = 0;
    
    commands.forEach(cmd => {
        const type = cmd[0];
        const values = cmd.slice(1).trim().split(/[\s,]+/).filter(v => v).map(parseFloat);
        
        switch (type) {
            case 'M': // Move to absolute
                currentX = values[0];
                currentY = values[1];
                startX = currentX;
                startY = currentY;
                coordinates.push(applyTransform(currentX, currentY, matrix));
                break;
                
            case 'm': // Move to relative
                currentX += values[0];
                currentY += values[1];
                startX = currentX;
                startY = currentY;
                coordinates.push(applyTransform(currentX, currentY, matrix));
                break;
                
            case 'L': // Line to absolute
                for (let i = 0; i < values.length; i += 2) {
                    currentX = values[i];
                    currentY = values[i + 1];
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                }
                break;
                
            case 'l': // Line to relative
                for (let i = 0; i < values.length; i += 2) {
                    currentX += values[i];
                    currentY += values[i + 1];
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                }
                break;
                
            case 'H': // Horizontal line absolute
                values.forEach(x => {
                    currentX = x;
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                });
                break;
                
            case 'h': // Horizontal line relative
                values.forEach(dx => {
                    currentX += dx;
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                });
                break;
                
            case 'V': // Vertical line absolute
                values.forEach(y => {
                    currentY = y;
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                });
                break;
                
            case 'v': // Vertical line relative
                values.forEach(dy => {
                    currentY += dy;
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                });
                break;
                
            case 'C': // Cubic Bezier absolute
                for (let i = 0; i < values.length; i += 6) {
                    // Add control points and end point
                    coordinates.push(applyTransform(values[i], values[i + 1], matrix));
                    coordinates.push(applyTransform(values[i + 2], values[i + 3], matrix));
                    currentX = values[i + 4];
                    currentY = values[i + 5];
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                }
                break;
                
            case 'c': // Cubic Bezier relative
                for (let i = 0; i < values.length; i += 6) {
                    coordinates.push(applyTransform(currentX + values[i], currentY + values[i + 1], matrix));
                    coordinates.push(applyTransform(currentX + values[i + 2], currentY + values[i + 3], matrix));
                    currentX += values[i + 4];
                    currentY += values[i + 5];
                    coordinates.push(applyTransform(currentX, currentY, matrix));
                }
                break;
                
            case 'z':
            case 'Z': // Close path
                if (currentX !== startX || currentY !== startY) {
                    coordinates.push(applyTransform(startX, startY, matrix));
                    currentX = startX;
                    currentY = startY;
                }
                break;
        }
    });
    
    return coordinates;
}

// Process all paths
const result = {};
paths.forEach(path => {
    const coords = pathToCoordinates(path.d, path.transform);
    result[path.id] = coords;
});

// Write output
fs.writeFileSync('path_coordinates.json', JSON.stringify(result, null, 2));

console.log(`Processed ${paths.length} paths`);
console.log('Output written to path_coordinates.json');
