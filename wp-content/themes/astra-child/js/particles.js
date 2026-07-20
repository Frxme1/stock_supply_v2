(function () {
    class Particles {
        constructor(options = {}) {
            // Create canvas container
            this.canvasContainer = document.createElement('div');
            this.canvasContainer.id = 'particles-container';
            this.canvasContainer.style.position = 'fixed';
            this.canvasContainer.style.top = '0';
            this.canvasContainer.style.left = '0';
            this.canvasContainer.style.width = '100vw';
            this.canvasContainer.style.height = '100vh';
            this.canvasContainer.style.zIndex = '-1'; // Sit behind content
            this.canvasContainer.style.pointerEvents = 'none'; // Don't block clicks
            this.canvasContainer.style.overflow = 'hidden';
            this.canvasContainer.setAttribute('aria-hidden', 'true');

            // Create canvas
            this.canvas = document.createElement('canvas');
            this.canvas.style.width = '100%';
            this.canvas.style.height = '100%';
            this.canvasContainer.appendChild(this.canvas);
            document.body.appendChild(this.canvasContainer);

            this.context = this.canvas.getContext('2d');
            this.quantity = options.quantity || 100;
            this.staticity = options.staticity || 50;
            this.ease = options.ease || 50;
            this.size = options.size || 0.4;
            this.color = options.color || '#cccccc';
            this.vx = options.vx || 0;
            this.vy = options.vy || 0;
            this.circles = [];

            this.mousePosition = { x: 0, y: 0 };
            this.mouse = { x: 0, y: 0 };
            this.canvasSize = { w: 0, h: 0 };
            this.dpr = window.devicePixelRatio || 1;

            this.rgb = this.hexToRgb(this.color);
            this.animate = this.animate.bind(this);

            this.init();
        }

        hexToRgb(hex) {
            hex = hex.replace("#", "");
            if (hex.length === 3) {
                hex = hex.split("").map((char) => char + char).join("");
            }
            const hexInt = parseInt(hex, 16);
            return [(hexInt >> 16) & 255, (hexInt >> 8) & 255, hexInt & 255];
        }

        init() {
            window.addEventListener('resize', () => this.initCanvas());
            window.addEventListener('mousemove', (e) => {
                this.mousePosition.x = e.clientX;
                this.mousePosition.y = e.clientY;
                this.onMouseMove();
            });
            this.initCanvas();
            window.requestAnimationFrame(this.animate);
        }

        initCanvas() {
            this.resizeCanvas();
            this.drawParticles();
        }

        resizeCanvas() {
            this.circles = [];
            this.canvasSize.w = this.canvasContainer.offsetWidth;
            this.canvasSize.h = this.canvasContainer.offsetHeight;
            this.canvas.width = this.canvasSize.w * this.dpr;
            this.canvas.height = this.canvasSize.h * this.dpr;
            this.context.scale(this.dpr, this.dpr);
        }

        circleParams() {
            const x = Math.floor(Math.random() * this.canvasSize.w);
            const y = Math.floor(Math.random() * this.canvasSize.h);
            const translateX = 0;
            const translateY = 0;
            const pSize = Math.floor(Math.random() * 2) + this.size;
            const alpha = 0;
            const targetAlpha = parseFloat((Math.random() * 0.6 + 0.1).toFixed(1));
            const dx = (Math.random() - 0.5) * 0.1;
            const dy = (Math.random() - 0.5) * 0.1;
            const magnetism = 0.1 + Math.random() * 4;
            return {
                x,
                y,
                translateX,
                translateY,
                size: pSize,
                alpha,
                targetAlpha,
                dx,
                dy,
                magnetism,
            };
        }

        drawCircle(circle, update = false) {
            const { x, y, translateX, translateY, size, alpha } = circle;
            this.context.translate(translateX, translateY);
            this.context.beginPath();
            this.context.arc(x, y, size, 0, 2 * Math.PI);
            this.context.fillStyle = `rgba(${this.rgb.join(", ")}, ${alpha})`;
            this.context.fill();
            this.context.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
            if (!update) {
                this.circles.push(circle);
            }
        }

        clearContext() {
            this.context.clearRect(0, 0, this.canvasSize.w, this.canvasSize.h);
        }

        drawParticles() {
            this.clearContext();
            for (let i = 0; i < this.quantity; i++) {
                this.drawCircle(this.circleParams());
            }
        }

        remapValue(value, start1, end1, start2, end2) {
            const remapped = ((value - start1) * (end2 - start2)) / (end1 - start1) + start2;
            return remapped > 0 ? remapped : 0;
        }

        onMouseMove() {
            const rect = this.canvas.getBoundingClientRect();
            const { w, h } = this.canvasSize;
            const x = this.mousePosition.x - rect.left - w / 2;
            const y = this.mousePosition.y - rect.top - h / 2;
            const inside = x < w / 2 && x > -w / 2 && y < h / 2 && y > -h / 2;
            if (inside) {
                this.mouse.x = x;
                this.mouse.y = y;
            }
        }

        animate() {
            this.clearContext();
            for (let i = this.circles.length - 1; i >= 0; i--) {
                const circle = this.circles[i];
                const edge = [
                    circle.x + circle.translateX - circle.size,
                    this.canvasSize.w - circle.x - circle.translateX - circle.size,
                    circle.y + circle.translateY - circle.size,
                    this.canvasSize.h - circle.y - circle.translateY - circle.size,
                ];
                const closestEdge = Math.min(...edge);
                const remapClosestEdge = parseFloat(this.remapValue(closestEdge, 0, 20, 0, 1).toFixed(2));

                if (remapClosestEdge > 1) {
                    circle.alpha += 0.02;
                    if (circle.alpha > circle.targetAlpha) {
                        circle.alpha = circle.targetAlpha;
                    }
                } else {
                    circle.alpha = circle.targetAlpha * remapClosestEdge;
                }

                circle.x += circle.dx + this.vx;
                circle.y += circle.dy + this.vy;
                circle.translateX += (this.mouse.x / (this.staticity / circle.magnetism) - circle.translateX) / this.ease;
                circle.translateY += (this.mouse.y / (this.staticity / circle.magnetism) - circle.translateY) / this.ease;

                this.drawCircle(circle, true);

                if (
                    circle.x < -circle.size ||
                    circle.x > this.canvasSize.w + circle.size ||
                    circle.y < -circle.size ||
                    circle.y > this.canvasSize.h + circle.size
                ) {
                    this.circles.splice(i, 1);
                    this.drawCircle(this.circleParams());
                }
            }
            window.requestAnimationFrame(this.animate);
        }
    }

    // Initialize particles when DOM is fully loaded
    document.addEventListener("DOMContentLoaded", function () {
        new Particles({
            quantity: 100,
            ease: 80,
            color: "#2b80b1ff", // Defaulting to a gray color to ensure visibility on light or dark backgrounds
        });
    });
})();
