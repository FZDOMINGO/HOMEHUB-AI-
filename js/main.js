// main.js file

// Function to load CSS and JS files
function loadAssets(assets) {
    assets.forEach(asset => {
        const element = document.createElement(asset.type);
        element.src = asset.src;
        element.onload = () => console.log(`Loaded ${asset.type}: ${asset.src}`);
        element.onerror = () => console.error(`Error loading ${asset.type}: ${asset.src}`);
        document.head.appendChild(element);
    });
}

// Example of usage
const cssFiles = [
    { type: 'link', src: 'style.css' },
];
const jsFiles = [
    { type: 'script', src: 'script.js' },
];

loadAssets([...cssFiles, ...jsFiles]);

console.log('Assets loading initiated.');
