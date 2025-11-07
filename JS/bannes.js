document.addEventListener("DOMContentLoaded", () => {

  /* ========== PRÉVIA DA IMAGEM ========== */
  const input = document.querySelector('input[name="imagemb"]');
  const previewBox = document.querySelector(".banner-thumb");

  if (input && previewBox) {
    input.addEventListener("change", () => {
      const file = input.files && input.files[0];
      if (!file) {
        previewBox.innerHTML = '<span class="text-muted">Prévia</span>';
        return;
      }
      if (!file.type.startsWith("image/")) {
        previewBox.innerHTML = '<span class="text-danger small">Arquivo inválido</span>';
        input.value = "";
        return;
      }
      const reader = new FileReader();
      reader.onload = e => {
        previewBox.innerHTML = `<img src="${e.target.result}" alt="Prévia"
          style="max-width:100%;max-height:100%;object-fit:cover;border-radius:8px;">`;
      };
      reader.readAsDataURL(file);
    });
  }

  /* ========== LISTAR CATEGORIAS ========== */
  async function listarcategorias(selector) {
    const sel = document.querySelector(selector);
    if (!sel) return;
    try {
      const r = await fetch("../PHP/cadastro_categoria.php?listar=1");
      if (!r.ok) throw new Error("Erro ao listar categorias");
      sel.innerHTML = await r.text();
    } catch {
      sel.innerHTML = "<option disabled>Erro ao carregar</option>";
    }
  }

  /* ========== LISTAR BANNERS ========== */
  function listarBanners(tabelaId) {
    const tbody = document.getElementById(tabelaId);
    if (!tbody) return;
    let byId = new Map();

    const esc = s => (s || '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const dtbr = iso => {
      if (!iso) return '-';
      const [y, m, d] = String(iso).split('-');
      return (y && m && d) ? `${d}/${m}/${y}` : '-';
    };

    const ph = () =>
      'data:image/svg+xml;base64,' + btoa(
        `<svg xmlns="http://www.w3.org/2000/svg" width="96" height="64">
          <rect width="100%" height="100%" fill="#eee"/>
          <text x="50%" y="50%" text-anchor="middle" font-size="10" fill="#999">SEM IMAGEM</text>
        </svg>`
      );

    fetch('../PHP/cadastro_bannes.php?listar=1', { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar banners');
        const arr = d.banners || [];
        byId = new Map();
        tbody.innerHTML = arr.length
          ? arr.map(b => {
              byId.set(String(b.id), b);
              let src = ph();
              if (b.imagem) {
                if (b.imagem.startsWith("data:image")) src = b.imagem;
                else if (b.imagem.startsWith("../") || b.imagem.startsWith("/"))
                  src = b.imagem;
                else src = `../IMG/${b.imagem}`;
              }
              return `
                <tr>
                  <td>${b.id}</td>
                  <td><img src="${src}" style="width:96px;height:64px;object-fit:cover;border-radius:6px"></td>
                  <td>${esc(b.descricao || '-')}</td>
                  <td>${dtbr(b.data_validade)}</td>
                  <td>${b.link ? `<a href="${esc(b.link)}" target="_blank">Abrir</a>` : '-'}</td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-info" data-id="${b.id}">Selecionar</button>
                    <button class="btn btn-sm btn-danger" data-id="${b.id}">Excluir</button>
                  </td>
                </tr>`;
            }).join('')
          : `<tr><td colspan="6" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;

        /* ==== EVENTOS DE BOTÕES ==== */
        tbody.onclick = async ev => {
          const btn = ev.target.closest('button');
          if (!btn) return;
          const id = btn.dataset.id;
          const banner = byId.get(String(id));
          if (!banner) return alert('Banner não encontrado.');

          // --- SELECIONAR ---
          if (btn.classList.contains('btn-info')) {
            preencherFormBanner(banner);
            window.scrollTo({ top: 0, behavior: 'smooth' });
          }

          // --- EXCLUIR ---
          if (btn.classList.contains('btn-danger')) {
            if (!confirm('Deseja realmente excluir este banner?')) return;
            const fd = new FormData();
            fd.append('acao', 'excluir');
            fd.append('id', id);
            const r = await fetch('../PHP/cadastro_bannes.php', { method: 'POST', body: fd });
            if (!r.ok) return alert('Falha ao excluir');
            alert('Banner excluído com sucesso!');
            listarBanners(tabelaId);
            limparForm();
          }
        };
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Erro: ${esc(err.message)}</td></tr>`;
      });
  }

  /* ========== PREENCHER FORM PARA EDIÇÃO ========== */
  function preencherFormBanner(b) {
    const form = document.getElementById('formBanner');
    if (!form) return;

    form.querySelector('input[name="descricao"]').value = b.descricao || '';
    form.querySelector('input[name="data_validade"]').value = b.data_validade || '';
    form.querySelector('input[name="link"]').value = b.link || '';
    form.querySelector('select[name="categoria"]').value = b.categoria_id ?? '';
    form.querySelector('input[name="id"]').value = b.id;

    const imgPreview = document.querySelector('.banner-thumb');
    if (b.imagem && imgPreview) {
      let src = b.imagem.startsWith("data:image") ? b.imagem
        : (b.imagem.startsWith("../") ? b.imagem : `../IMG/${b.imagem}`);
      imgPreview.innerHTML = `<img src="${src}" style="max-width:100%;max-height:100%;object-fit:cover;border-radius:8px;">`;
    }

    const btn = form.querySelector('button[type="submit"]');
    btn.textContent = 'Salvar alterações';
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-success');
  }

  /* ========== LIMPAR FORM ========== */
  function limparForm() {
    const form = document.getElementById('formBanner');
    form.reset();
    const preview = document.querySelector('.banner-thumb');
    if (preview) preview.innerHTML = '<span class="text-muted">Prévia</span>';
    const btn = form.querySelector('button[type="submit"]');
    btn.textContent = 'Cadastrar';
    btn.classList.remove('btn-success');
    btn.classList.add('btn-primary');
  }

  document.getElementById('formBanner').addEventListener('reset', limparForm);

  // Inicialização
  listarcategorias("#categorialistar");
  listarBanners("tabelaBanners");
});
