import fs from 'node:fs/promises';
import path from 'node:path';
import {Presentation, PresentationFile} from '@oai/artifact-tool';

const root = path.resolve('.');
const lessons = JSON.parse(await fs.readFile(path.join(root, 'docker/course-content-repair.json'), 'utf8'));
const courses = [
  {id: 2, code: 'PSH', name: 'Psychologie humaine', filename: 'PSH_Psychologie_Humaine.pptx', basics: 'La psychologie étudie les comportements observables et les processus mentaux. L’observation, l’expérimentation et les enquêtes apportent des données complémentaires.', practice: 'Comparez deux courants de la psychologie : question posée, méthode utilisée et limites. Expliquez ensuite comment une écoute attentive peut améliorer une consultation.', source: 'https://www.apa.org/topics/psychology'},
  {id: 3, code: 'EPID101', name: 'Introduction à l’épidémiologie', filename: 'EPID101_Introduction_Epidemiologie.pptx', basics: 'La prévalence décrit les cas existants dans une population définie. L’incidence décrit les nouveaux cas pendant une période. Toujours préciser le dénominateur et la période.', source: 'https://www.cdc.gov/field-epi-manual/php/chapters/describing-epi-data.html'},
  {id: 4, code: 'BIOS201', name: 'Biostatistiques appliquées', filename: 'BIOS201_Biostatistiques.pptx', basics: 'Décrivez d’abord les variables et les données manquantes. Présentez une estimation et son incertitude. Une valeur p ne mesure pas la taille d’un effet.', source: 'https://www.cdc.gov/field-epi-manual/php/chapters/analyze-interpret-data.html'},
  {id: 5, code: 'EPID301', name: 'Épidémiologie des maladies chroniques', filename: 'EPID301_Maladies_Chroniques.pptx', basics: 'Les principales maladies non transmissibles incluent maladies cardiovasculaires, cancers, maladies respiratoires chroniques et diabète. Leur prévention agit sur les facteurs de risque et les milieux de vie.', source: 'https://www.who.int/news-room/fact-sheets/detail/noncommunicable-diseases'},
  {id: 6, code: 'SCOM101', name: 'Principes de santé communautaire', filename: 'SCOM101_Sante_Communautaire.pptx', basics: 'Le diagnostic communautaire associe données de santé et expérience des habitants. La participation doit aussi porter sur le choix des priorités, l’action et son suivi.', source: 'https://www.who.int/publications/m/item/community-engagement-for-quality--people-centred-health-services'},
  {id: 7, code: 'PROM201', name: 'Stratégies de promotion de la santé', filename: 'PROM201_Promotion_Sante.pptx', basics: 'La Charte d’Ottawa décrit cinq axes : politiques publiques, milieux favorables, action communautaire, aptitudes individuelles et réorientation des services de santé.', source: 'https://www.who.int/publications/i/item/ottawa-charter-for-health-promotion'},
  {id: 8, code: 'DIAG', name: 'Diagnostic communautaire', filename: 'RHS_Niamey_Groupe5_v3.pptx', basics: 'Définissez la question du diagnostic, diversifiez les sources, écoutez les groupes moins visibles, puis restituez les résultats avec leurs limites.', source: 'https://www.who.int/publications/m/item/community-engagement-for-quality--people-centred-health-services'},
];

function addText(slide, value, left, top, width, height, size, color, bold = false) {
  const box = slide.shapes.add({geometry: 'textbox', position: {left, top, width, height}, fill: 'none', line: {fill: 'none', width: 0}});
  box.text = value;
  box.text.style = {typeface: 'Arial', fontSize: size, color, bold, autoFit: 'shrinkText'};
}
function addSlide(p, title, body, source, index) {
  const slide = p.slides.add();
  slide.background.fill = '#FFFFFF';
  addText(slide, title, 72, 62, 1130, 110, 40, '#005489', true);
  addText(slide, body, 78, 215, 1110, 355, 29, '#253746');
  addText(slide, `ISP · ${index}`, 78, 650, 400, 30, 16, '#6E3A41');
  slide.speakerNotes.textFrame.setText(`Source : ${source}`);
  return slide;
}
for (const course of courses) {
  const p = Presentation.create({slideSize: {width: 1280, height: 720}});
  addSlide(p, course.name, `${course.code} · Support pédagogique de synthèse\n\nObjectif : relier les notions du cours à une situation de santé publique.`, course.source, 1);
  addSlide(p, 'Notions essentielles', course.basics, course.source, 2);
  const selected = lessons.filter(x => x.course === course.id).slice(0, 2);
  if (selected.length) {
    for (const [i, lesson] of selected.entries()) {
      addSlide(p, lesson.title, `${lesson.lesson}\n\nÀ appliquer : ${lesson.exercise}`, lesson.reference, i + 3);
    }
  } else {
    addSlide(p, 'Mettre les notions en pratique', course.practice, course.source, 3);
  }
  addSlide(p, 'Pour poursuivre', `Lisez les pages de cours et réalisez les exercices dans Moodle.\n\nRéférence principale : ${course.source}`, course.source, p.slides.length + 1);
  const draft = path.join(root, '.artifacts/build', course.filename);
  await (await PresentationFile.exportPptx(p)).save(draft);
  console.log(draft);
}
