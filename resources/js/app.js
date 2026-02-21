import confetti from 'canvas-confetti';

// Expose confetti globally for Livewire/Alpine inline scripts (bingo card celebration)
window.confetti = confetti;

// Bingo card completion confetti – listener in app.js so it runs regardless of wire:navigate
function runBingoConfetti() {
  if (typeof confetti !== 'function') return;
  const delay = 280;
  for (let i = 0; i < 3; i++) {
    const n = i;
    const x = 0.25 + Math.random() * 0.5;
    const y = 0.25 + Math.random() * 0.5;
    setTimeout(() => {
      confetti({
        particleCount: 400,
        spread: 360,
        origin: { x, y },
        disableForReducedMotion: true,
      });
    }, n * delay);
  }
}
document.addEventListener('bingo-complete', runBingoConfetti);
