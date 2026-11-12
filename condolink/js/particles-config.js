tsParticles.load("tsparticles", {
  fpsLimit: 60,
  particles: {
    number: {
      value: 60, // Quantidade de pontos na tela
      density: {
        enable: true,
        value_area: 800
      }
    },
    color: {
      value: ["#002b5c", "#d4af37"] // Cores dos pontos: Azul escuro e Dourado
    },
    shape: {
      type: "circle"
    },
    opacity: {
      value: 0.5,
      random: false
    },
    size: {
      value: { min: 2, max: 4 }
    },
    links: {
      enable: true,
      distance: 150,
      color: "#002b5c", // Cor das linhas de conexão
      opacity: 0.2,
      width: 1
    },
    move: {
      enable: true,
      speed: 1.5, // Velocidade do movimento suave
      direction: "none",
      random: false,
      straight: false,
      outModes: {
        default: "bounce"
      }
    }
  },
  interactivity: {
    events: {
      onHover: {
        enable: true,
        mode: "grab" // Cria conexões com o ponteiro do mouse
      },
      onClick: {
        enable: true,
        mode: "push" // Adiciona mais partículas ao clicar
      }
    },
    modes: {
      grab: {
        distance: 140,
        links: {
          opacity: 0.5
        }
      },
      push: {
        quantity: 3
      }
    }
  },
  detectRetina: true
});