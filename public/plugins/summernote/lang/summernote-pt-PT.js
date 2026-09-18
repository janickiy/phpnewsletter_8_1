/*!
 * 
 * Super simple WYSIWYG editor v0.9.1
 * https://summernote.org
 *
 * Copyright 2013~ Hackerwins and contributors
 * Summernote may be freely distributed under the MIT license.
 *
 * Date: 2024-10-09T10:28Z
 *
 */
(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else {
		var a = factory();
		for(var i in a) (typeof exports === 'object' ? exports : root)[i] = a[i];
	}
})(self, () => {
return /******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
(function ($) {
  $.extend(true, $.summernote.lang, {
    'pt-PT': {
      font: {
        bold: 'Negrito',
        italic: 'Itálico',
        underline: 'Sublinhado',
        clear: 'Remover estilo da fonte',
        height: 'Altura da linha',
        name: 'Fonte',
        strikethrough: 'Riscado',
        subscript: 'Subscrito',
        superscript: 'Sobrescrito',
        size: 'Tamanho da fonte',
        sizeunit: 'Unidade do tamanho da fonte'
      },
      image: {
        image: 'Imagem',
        insert: 'Inserir imagem',
        resizeFull: 'Redimensionar Completo',
        resizeHalf: 'Redimensionar Metade',
        resizeQuarter: 'Redimensionar Um Quarto',
        resizeNone: 'Tamanho original',
        floatLeft: 'Flutuar à esquerda',
        floatRight: 'Flutuar à direita',
        floatNone: 'Sem flutuação',
        shapeRounded: 'Forma: Arredondado',
        shapeCircle: 'Forma: Círculo',
        shapeThumbnail: 'Forma: Miniatura',
        shapeNone: 'Forma: Nenhum',
        dragImageHere: 'Arraste uma imagem para aqui',
        dropImage: 'Arraste uma imagem ou texto',
        selectFromFiles: 'Selecione a partir dos arquivos',
        maximumFileSize: 'Tamanho máximo do ficheiro',
        maximumFileSizeError: 'O tamanho do ficheiro é maior que o permitido.',
        url: 'Endereço da imagem',
        remove: 'Remover Imagem',
        original: 'Original'
      },
      video: {
        video: 'Vídeo',
        videoLink: 'Link para vídeo',
        insert: 'Inserir vídeo',
        url: 'URL do vídeo?',
        providers: '(YouTube, Google Drive, Vimeo, Vine, Instagram, DailyMotion ou Youku)'
      },
      link: {
        link: 'Link',
        insert: 'Inserir ligação',
        unlink: 'Remover ligação',
        edit: 'Editar',
        textToDisplay: 'Texto para exibir',
        url: 'Para que endereço deve ir esta ligação?',
        openInNewWindow: 'Abrir numa nova janela'
      },
      table: {
        table: 'Tabela',
        addRowAbove: 'Adicionar linha acima',
        addRowBelow: 'Adicionar linha abaixo',
        addColLeft: 'Adicionar coluna à Esquerda',
        addColRight: 'Adicionar coluna à Direita',
        delRow: 'Excluir linha',
        delCol: 'Excluir coluna',
        delTable: 'Excluir tabela'
      },
      hr: {
        insert: 'Inserir linha horizontal'
      },
      style: {
        style: 'Estilo',
        p: 'Parágrafo',
        blockquote: 'Citação',
        pre: 'Código',
        h1: 'Título 1',
        h2: 'Título 2',
        h3: 'Título 3',
        h4: 'Título 4',
        h5: 'Título 5',
        h6: 'Título 6'
      },
      lists: {
        unordered: 'Lista com marcadores',
        ordered: 'Lista numerada'
      },
      options: {
        help: 'Ajuda',
        fullscreen: 'Janela Completa',
        codeview: 'Ver código-fonte'
      },
      paragraph: {
        paragraph: 'Parágrafo',
        outdent: 'Menor tabulação',
        indent: 'Maior tabulação',
        left: 'Alinhar à esquerda',
        center: 'Alinhar ao centro',
        right: 'Alinha à direita',
        justify: 'Justificado'
      },
      color: {
        recent: 'Cor recente',
        more: 'Mais cores',
        background: 'Fundo',
        foreground: 'Fonte',
        transparent: 'Transparente',
        setTransparent: 'Fundo transparente',
        reset: 'Restaurar',
        resetToDefault: 'Restaurar padrão',
        cpSelect: 'Selecionar'
      },
      shortcut: {
        shortcuts: 'Atalhos do teclado',
        close: 'Fechar',
        textFormatting: 'Formatação de texto',
        action: 'Ação',
        paragraphFormatting: 'Formatação de parágrafo',
        documentStyle: 'Estilo de documento',
        extraKeys: 'Teclas adicionais'
      },
      help: {
        'escape': 'Sair',
        'insertParagraph': 'Inserir Parágrafo',
        'undo': 'Desfazer o último comando',
        'redo': 'Refazer o último comando',
        'tab': 'Maior tabulação',
        'untab': 'Menor tabulação',
        'bold': 'Colocar em negrito',
        'italic': 'Colocar em itálico',
        'underline': 'Colocar em sublinhado',
        'strikethrough': 'Colocar em riscado',
        'removeFormat': 'Limpar o estilo',
        'justifyLeft': 'Definir alinhado à esquerda',
        'justifyCenter': 'Definir alinhado ao centro',
        'justifyRight': 'Definir alinhado à direita',
        'justifyFull': 'Definir justificado',
        'insertUnorderedList': 'Alternar lista não ordenada',
        'insertOrderedList': 'Alternar lista ordenada',
        'outdent': 'Recuar parágrafo atual',
        'indent': 'Avançar parágrafo atual',
        'formatPara': 'Alterar formato do bloco para parágrafo',
        'formatH1': 'Alterar formato do bloco para Título 1',
        'formatH2': 'Alterar formato do bloco para Título 2',
        'formatH3': 'Alterar formato do bloco para Título 3',
        'formatH4': 'Alterar formato do bloco para Título 4',
        'formatH5': 'Alterar formato do bloco para Título 5',
        'formatH6': 'Alterar formato do bloco para Título 6',
        'insertHorizontalRule': 'Inserir linha horizontal',
        'linkDialog.show': 'Mostrar a caixa de diálogo de ligação'
      },
      history: {
        undo: 'Desfazer',
        redo: 'Refazer'
      },
      specialChar: {
        specialChar: 'CARACTERES ESPECIAIS',
        select: 'Selecionar caracteres especiais'
      },
      output: {
        noSelection: 'Nada selecionado!'
      }
    }
  });
})(jQuery);
/******/ 	return __webpack_exports__;
/******/ })()
;
});
//# sourceMappingURL=summernote-pt-PT.js.map
