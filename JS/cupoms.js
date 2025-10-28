function listarcategorias(nomeid){
(async () => {
    // selecionando o elemento html da tela de cadastro de produtos
    const sel = document.querySelector(nomeid);
    try {
        // criando a váriavel que guardar os dados vindo do php, que estão no metodo de listar
        const r = await fetch("../PHP/cadastro_categoria.php?listar=1");
        // se o retorno do php vier false, significa que não foi possivel listar os dados
        if (!r.ok) throw new Error("Falha ao listar categorias!");
        /* se vier dados do php, ele joga as 
        informações dentro do campo html em formato de texto
        innerHTML- inserir dados em elementos html
        */
        sel.innerHTML = await r.text();
    } catch (e) {
        // se dê erro na listagem, aparece Erro ao carregar dentro do campo html
        sel.innerHTML = "<option disable>Erro ao carregar</option>"
    }
})();
}

// função de listar banners em tabela
function listarcupoms(lcupoms) {
  document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById(lcupoms);
    const url   = '../php/cadastro_cupoms.php?listar=1&format=json';

    // Função para escapar caracteres especiais
    const esc = s => (s||'').replace(/[&<>"']/g, c => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[c]));

    // Formata data para DD/MM/AAAA
    const formatData = d => {
      if (!d) return '-';
      const dt = new Date(d);
      return dt.toLocaleDateString('pt-BR');
    };

    
    // Monta cada linha da tabela
    const row = b => `
      <tr>
        <td>${Number(b.id) || ''}</td>
        <td>${esc(b.nome || '-')}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-warning" data-id="${b.id}">
            <i class="bi bi-pencil"></i> Editar
          </button>
          <button class="btn btn-sm btn-danger" data-id="${b.id}">
            <i class="bi bi-trash"></i> Excluir
          </button>
        </td>
      </tr>`;

    // Requisição para o PHP
    fetch(url, { cache: 'no-store' })
      .then(r => r.json())
      .then(d => {
        if (!d.ok) throw new Error(d.error || 'Erro ao listar cupoms');
        const banners = d.banners || [];
        tbody.innerHTML = banners.length
          ? banners.map(row).join('')
          : `<tr><td colspan="5" class="text-center text-muted">Nenhum banner cadastrado.</td></tr>`;
      })
      .catch(err => {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Falha ao carregar: ${esc(err.message)}</td></tr>`;
      });
  });
}

// Chama a função para listar cupoms
listarcupoms('lcupoms');

