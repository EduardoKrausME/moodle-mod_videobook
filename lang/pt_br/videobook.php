<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings for Video Book.
 *
 * @package   mod_videobook
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['actions'] = 'Ações';
$string['addchapter'] = 'Adicionar capítulo';
$string['addresource'] = 'Adicionar material';
$string['allowseek'] = 'Permitir avançar livremente no vídeo';
$string['allowseek_help'] = 'Quando desativado, o estudante pode voltar para trechos já assistidos, mas não pode avançar para partes que ainda não assistiu de verdade.';
$string['attachments'] = 'Arquivos e documentos';
$string['available'] = 'Disponível';
$string['backtobook'] = 'Voltar ao Video Book';
$string['bookprogress'] = 'Progresso do Video Book';
$string['captions'] = 'Legendas';
$string['captions_help'] = 'Envie arquivos de legenda WebVTT ou SRT. Os arquivos SRT são convertidos para WebVTT ao salvar o capítulo.';
$string['chapteranalytics'] = 'Progresso por aula';
$string['chaptercompleted'] = 'Capítulo concluído.';
$string['chapterdeleted'] = 'Capítulo excluído.';
$string['chapterduplicated'] = 'Aula duplicada.';
$string['chapterhasnovideo'] = 'Este capítulo não possui vídeo.';
$string['chapterhasvideo'] = 'Um capítulo com vídeo é concluído automaticamente pelo percentual assistido.';
$string['chapterimage'] = 'Imagem do capítulo';
$string['chapterlocked'] = 'Este capítulo está bloqueado até que os capítulos anteriores sejam concluídos.';
$string['chapternavigation'] = 'Navegação entre capítulos';
$string['chapterprogress'] = 'Progresso do capítulo';
$string['chapters'] = 'Capítulos';
$string['chaptersaved'] = 'Capítulo salvo.';
$string['chaptersimported'] = '{$a} aulas importadas.';
$string['chaptertext'] = 'Texto explicativo';
$string['chaptertitle'] = 'Título do capítulo';
$string['chaptervisible'] = 'Visível para os alunos';
$string['chapterxofy'] = 'Capítulo {$a->current} de {$a->total}';
$string['complementarylinks'] = 'Links complementares';
$string['complementarylinks_help'] = 'Informe um link por linha. Use somente a URL ou Título|URL. Exemplo: Documentação Moodle|https://moodle.org/';
$string['completedcount'] = 'Concluíram';
$string['completionallchapters'] = 'Exigir a conclusão de todos os capítulos';
$string['completiondetail:allchapters'] = 'Concluir todos os capítulos do Video Book';
$string['completionended'] = 'Assistir o vídeo inteiro';
$string['completionmanual'] = 'Marcar manualmente como concluída';
$string['completionpercent'] = 'Assistir um percentual do vídeo';
$string['completionpercentormanual'] = 'Assistir o percentual ou marcar manualmente';
$string['completionrequiresvideo'] = 'Este critério de conclusão exige um vídeo.';
$string['completiontype'] = 'Conclusão da aula';
$string['completiontype_help'] = 'Define quando esta aula é considerada concluída. A mesma regra controla a liberação sequencial quando a navegação sequencial estiver ativada.';
$string['completionview'] = 'Abrir a aula';
$string['contentorder'] = 'Ordem do conteúdo';
$string['contentorder_help'] = 'Escolha uma ordem simples para vídeo, conteúdo, transcrição e materiais, sem transformar a aula em um construtor livre de páginas.';
$string['continuestudying'] = 'Continuar estudando';
$string['copytitle'] = '{$a} (cópia)';
$string['csvfile'] = 'Arquivo CSV';
$string['csvformathelp'] = 'Use as colunas title, video_url, content, transcript, section, order, visible, completion, minimum_percent e links. Separadores por vírgula e ponto e vírgula são aceitos.';
$string['csvmissing'] = 'O arquivo CSV enviado não foi encontrado.';
$string['csvtitlemissing'] = 'O CSV precisa conter a coluna title.';
$string['delete'] = 'Excluir';
$string['deletechapter'] = 'Excluir capítulo';
$string['deletechapterconfirm'] = 'Excluir o capítulo "{$a}" e todo o progresso dos estudantes armazenado para ele?';
$string['dragtoorder'] = 'Arraste para reordenar';
$string['duplicate'] = 'Duplicar';
$string['edit'] = 'Editar';
$string['editchapter'] = 'Editar capítulo';
$string['editresource'] = 'Editar material';
$string['errormaxfiles'] = 'Apenas um arquivo pode ser enviado.';
$string['hide'] = 'Ocultar';
$string['importchapters'] = 'Importar aulas por CSV';
$string['inprogresscount'] = 'Em andamento';
$string['invalidchapter'] = 'Capítulo inválido do Video Book.';
$string['invalidchapterorder'] = 'Ordem de aulas inválida.';
$string['invaliddirection'] = 'Direção inválida para mover o capítulo.';
$string['invalidpercentage'] = 'Informe um percentual de 1 a 100.';
$string['invalidvideosource'] = 'Fonte de vídeo inválida.';
$string['invalidvimeourl'] = 'A URL do Vimeo é inválida.';
$string['invalidyoutubeurl'] = 'A URL ou o identificador do vídeo do YouTube é inválido.';
$string['lastaccess'] = 'Último acesso';
$string['layoutcontinuous'] = 'Conteúdo contínuo';
$string['layoutmode'] = 'Layout da aula';
$string['layoutmode_help'] = 'Abas mantêm conteúdo, transcrição e materiais na mesma página do vídeo em painéis separados. Conteúdo contínuo apresenta os blocos um após o outro na ordem configurada.';
$string['layouttabs'] = 'Abas abaixo do vídeo';
$string['locked'] = 'Bloqueada';
$string['managechapters'] = 'Gerenciar capítulos';
$string['manageresources'] = 'Gerenciar materiais';
$string['manualcompletiondisabled'] = 'A conclusão manual não está habilitada para esta aula.';
$string['markchaptercomplete'] = 'Marcar capítulo como concluído';
$string['materials'] = 'Materiais';
$string['minimumpercent'] = 'Percentual mínimo do vídeo';
$string['minimumpercent_help'] = 'Percentual de conteúdo único do vídeo que o estudante precisa realmente assistir para este capítulo ser considerado concluído.';
$string['modulename'] = 'Video Book';
$string['modulename_help'] = 'Cria um livro multimídia organizado em capítulos, principalmente em vídeo, com acompanhamento real, retomada, recursos e relatório por capítulo.';
$string['modulenameplural'] = 'Video Books';
$string['movedown'] = 'Mover para baixo';
$string['moveup'] = 'Mover para cima';
$string['navigationfree'] = 'Navegação livre';
$string['navigationheader'] = 'Navegação e reprodução';
$string['navigationmode'] = 'Navegação entre capítulos';
$string['navigationsequential'] = 'Navegação sequencial';
$string['never'] = 'Nunca';
$string['next'] = 'Próximo';
$string['nextlocked'] = 'Próximo capítulo bloqueado';
$string['noactivities'] = 'Não há atividades Video Book neste curso.';
$string['nochaptersstudent'] = 'Este Video Book ainda não possui capítulos disponíveis.';
$string['nochaptersteacher'] = 'Ainda não há capítulos. Adicione o primeiro capítulo para iniciar o Video Book.';
$string['noresults'] = 'Nenhuma aula corresponde à busca.';
$string['nostudents'] = 'Nenhum estudante foi encontrado para esta atividade.';
$string['notstartedcount'] = 'Não iniciaram';
$string['ordercontentvideotranscriptresources'] = 'Conteúdo → Vídeo → Transcrição → Materiais';
$string['ordervideocontenttranscriptresources'] = 'Vídeo → Conteúdo → Transcrição → Materiais';
$string['ordervideoresourcescontenttranscript'] = 'Vídeo → Materiais → Conteúdo → Transcrição';
$string['ordervideotranscriptcontentresources'] = 'Vídeo → Transcrição → Conteúdo → Materiais';
$string['overall'] = 'Progresso geral';
$string['pluginadministration'] = 'Administração do Video Book';
$string['pluginname'] = 'Video Book';
$string['previous'] = 'Anterior';
$string['privacy:metadata:progress'] = 'Armazena o progresso de cada usuário em cada capítulo do Video Book.';
$string['privacy:metadata:progress:duration'] = 'Duração conhecida do vídeo.';
$string['privacy:metadata:progress:lastaccess'] = 'Última data em que o capítulo foi acessado.';
$string['privacy:metadata:progress:lastposition'] = 'Última posição de reprodução salva.';
$string['privacy:metadata:progress:percent'] = 'Percentual assistido calculado pelo servidor.';
$string['privacy:metadata:progress:segments'] = 'Intervalos do vídeo efetivamente assistidos.';
$string['privacy:metadata:progress:status'] = 'Situação do capítulo: não iniciado, em andamento ou concluído.';
$string['privacy:metadata:progress:uniquewatched'] = 'Quantidade de conteúdo único do vídeo assistido.';
$string['privacy:metadata:progress:userid'] = 'Identificador do usuário.';
$string['privacy:progresspath'] = 'Progresso dos capítulos';
$string['progressreset'] = 'Progresso resetado.';
$string['reporttitle'] = 'Relatório de progresso do Video Book';
$string['resetprogress'] = 'Resetar progresso';
$string['resetprogressconfirm'] = 'Resetar todo o progresso do Video Book de {$a}?';
$string['resourcedeleted'] = 'Material excluído.';
$string['resourcedescription'] = 'Descrição curta';
$string['resourcefile'] = 'Arquivo do material';
$string['resourcesaved'] = 'Material salvo.';
$string['resourcesheader'] = 'Recursos do capítulo';
$string['resourcesource'] = 'Origem do material';
$string['resourcesourcefile'] = 'Arquivo enviado';
$string['resourcesourceurl'] = 'URL externa';
$string['resourcetitle'] = 'Título do material';
$string['resourcetype'] = 'Tipo de material';
$string['resourcetype:audio'] = 'Áudio / podcast';
$string['resourcetype:complementary'] = 'Arquivo complementar';
$string['resourcetype:link'] = 'Link externo';
$string['resourcetype:other'] = 'Outro';
$string['resourcetype:pdf'] = 'PDF / material da aula';
$string['resourcetype:slides'] = 'Slides';
$string['resourceurl'] = 'URL do material';
$string['resourcevisible'] = 'Visível para os alunos';
$string['resumeask'] = 'Perguntar ao estudante';
$string['resumeautomatic'] = 'Continuar automaticamente';
$string['resumefromstart'] = 'Sempre iniciar do começo';
$string['resumeno'] = 'Começar do início';
$string['resumeplayback'] = 'Retomada da reprodução';
$string['resumequestion'] = 'Você parou em {$a}. Deseja continuar desse ponto?';
$string['resumeyes'] = 'Continuar';
$string['savechapter'] = 'Salvar capítulo';
$string['saveorder'] = 'Salvar ordem';
$string['saveresource'] = 'Salvar material';
$string['searchchapters'] = 'Buscar aulas';
$string['searchresults'] = 'Resultados da busca';
$string['sectiontitle'] = 'Seção / módulo';
$string['sectiontitle_help'] = 'Agrupador opcional exibido no menu, por exemplo “Módulo 1 — Introdução”. As aulas continuam sendo uma lista simples e ordenada.';
$string['seekblocked'] = 'Você não pode avançar para um trecho que ainda não assistiu.';
$string['show'] = 'Exibir';
$string['sourcenone'] = 'Sem vídeo';
$string['sourceupload'] = 'Vídeo enviado ao Moodle';
$string['sourceurl'] = 'URL direta de vídeo ou HLS';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['status0'] = 'Não iniciado';
$string['status1'] = 'Em andamento';
$string['status2'] = 'Concluído';
$string['stoppedat'] = 'Parou em {$a}';
$string['structuredresources'] = 'Materiais estruturados da aula';
$string['student'] = 'Aluno';
$string['trackingerror'] = 'Não foi possível sincronizar o progresso do vídeo.';
$string['transcript'] = 'Transcrição';
$string['transcript_help'] = 'Transcrição nativa da aula. Pode conter texto formatado, links e arquivos incorporados e participa da busca dentro do Video Book.';
$string['videobook:addinstance'] = 'Adicionar um novo Video Book';
$string['videobook:managechapters'] = 'Gerenciar capítulos do Video Book';
$string['videobook:resetprogress'] = 'Resetar progresso do Video Book';
$string['videobook:view'] = 'Visualizar o Video Book';
$string['videobook:viewreport'] = 'Visualizar relatórios do Video Book';
$string['videobookname'] = 'Nome do Video Book';
$string['videofile'] = 'Arquivo de vídeo';
$string['videofilemissing'] = 'O arquivo de vídeo enviado não foi encontrado.';
$string['videosource'] = 'Fonte do vídeo';
$string['videourl'] = 'URL do vídeo';
$string['viewreport'] = 'Ver relatório';
$string['ws:accepted'] = 'Indica se a atualização de progresso foi aceita.';
$string['ws:bookpercent'] = 'Percentual geral de conclusão do Video Book.';
$string['ws:chapterid'] = 'Identificador do capítulo.';
$string['ws:cmid'] = 'Identificador do módulo do curso.';
$string['ws:completed'] = 'Indica se o capítulo foi concluído.';
$string['ws:correctposition'] = 'Posição de reprodução aprovada pelo servidor.';
$string['ws:currentposition'] = 'Posição atual do player.';
$string['ws:duration'] = 'Duração do vídeo informada pelo player.';
$string['ws:lastposition'] = 'Última posição de reprodução armazenada.';
$string['ws:percent'] = 'Percentual assistido calculado pelo servidor.';
$string['ws:playbackrate'] = 'Velocidade atual de reprodução.';
$string['ws:playerstate'] = 'Estado atual do player.';
$string['ws:reason'] = 'Resultado da validação do servidor.';
$string['ws:segmentend'] = 'Fim do intervalo contínuo assistido.';
$string['ws:segments'] = 'Intervalos assistidos consolidados em JSON.';
$string['ws:segmentstart'] = 'Início do intervalo contínuo assistido.';
$string['ws:status'] = 'Situação do progresso do capítulo.';
