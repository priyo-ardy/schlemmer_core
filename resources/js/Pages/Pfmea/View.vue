<template>
  <div class="p-4 overflow-x-auto font-sans text-xs text-black bg-white">
    <div class="min-w-[1500px] border-2 border-black">
      
      <div class="flex border-b-2 border-black">
        <div class="flex items-center justify-center w-32 p-2 border-r border-black">
          <div class="flex items-center justify-center w-16 h-12 text-gray-500 bg-gray-200 rounded-full">
            LOGO
          </div>
        </div>

        <div class="flex flex-col flex-1">
          <div class="p-1 font-bold text-center border-b border-black">
            Schlemmer Automotive Indonesia
          </div>
          <div class="flex items-center justify-center flex-1 p-2 text-lg font-bold text-center">
            PFMEA
          </div>
        </div>

        <div class="flex flex-col w-64 border-l border-black">
          <div class="flex border-b border-black bg-yellow-300">
            <div class="flex-1 p-1 border-r border-black">Issue Date</div>
            <div class="w-16 p-1">Page:</div>
          </div>
          <div class="flex border-b border-black bg-yellow-300">
            <div class="flex-1 p-1 border-r border-black">Issuing Dept.: R&D Dept.</div>
            <div class="w-16 p-1">Version:</div>
          </div>
          <div class="flex flex-1 text-center bg-yellow-300">
            <div class="flex-1 p-1 border-r border-black">Approved</div>
            <div class="flex-1 p-1 border-r border-black">Reviewed</div>
            <div class="flex-1 p-1">Prepared</div>
          </div>
        </div>
      </div>

      <div class="p-1 text-right border-b border-black bg-yellow-300">
        No.: ISLMGxxxxFMEA01(00)
      </div>

      <div class="flex border-b border-black">
        <div class="flex flex-col w-48 border-r border-black">
          <div class="flex items-center p-1 border-b border-black">
            <span class="w-16">Scope</span>
            <span class="flex-1 px-1 border-l border-black">Prototype</span>
            <span class="w-8 border-l border-black"></span>
          </div>
          <div class="flex items-center p-1 border-b border-black">
            <span class="w-16"></span>
            <span class="flex-1 px-1 border-l border-black">Pre-Launch</span>
            <span class="w-8 border-l border-black"></span>
          </div>
          <div class="flex items-center p-1 border-b border-black">
            <span class="w-16"></span>
            <span class="flex-1 px-1 border-l border-black border-dashed">Containment EPC</span>
            <span class="flex items-center justify-center w-8 border-l border-black">✓</span>
          </div>
          <div class="flex items-center p-1">
            <span class="w-16"></span>
            <span class="flex-1 px-1 border-l border-black">Mass Production</span>
            <span class="w-8 border-l border-black"></span>
          </div>
        </div>

        <div class="flex flex-col flex-1 border-r border-black">
          <div class="flex border-b border-black bg-yellow-300">
            <div class="w-1/2 p-1 border-r border-black">Project Name:</div>
            <div class="w-1/2 p-1">Part Name:</div>
          </div>
          <div class="flex border-b border-black bg-yellow-300">
            <div class="w-1/2 p-1 border-r border-black">Key Date:</div>
            <div class="w-1/2 p-1">Process Responsibility: Development, Manufacturing, Quality, Business, Logistics</div>
          </div>
          <div class="flex flex-1">
            <table class="w-full text-center border-collapse">
              <thead>
                <tr>
                  <th colspan="6" class="p-1 font-normal border-b border-black">Revision History</th>
                </tr>
                <tr class="bg-yellow-300">
                  <th class="w-10 p-1 font-normal border-b border-r border-black">Ver.</th>
                  <th class="w-24 p-1 font-normal border-b border-r border-black">Date</th>
                  <th class="p-1 font-normal border-b border-r border-black">Content</th>
                  <th class="w-24 p-1 font-normal border-b border-r border-black">Approved</th>
                  <th class="w-24 p-1 font-normal border-b border-r border-black">Reviewed</th>
                  <th class="w-24 p-1 font-normal border-b border-black">Prepared</th>
                </tr>
              </thead>
              <tbody class="bg-yellow-300">
                <tr>
                  <td class="border-r border-black">1.0</td>
                  <td class="border-r border-black"></td>
                  <td class="border-r border-black"></td>
                  <td class="border-r border-black"></td>
                  <td class="border-r border-black"></td>
                  <td></td>
                </tr>
                <tr>
                  <td class="border-t border-r border-black">2.0</td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-black"></td>
                </tr>
                <tr>
                  <td class="border-t border-r border-black">3.0</td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-r border-black"></td>
                  <td class="border-t border-black"></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="w-48 bg-yellow-300">
          <div class="p-1">Part No./Level:</div>
        </div>
      </div>

      <div class="flex border-b border-black">
        <div class="w-24 p-1 border-r border-black">Core Team:</div>
        <div class="flex-1 p-1 bg-yellow-300">
          Nanang, Adnan, Rulyanto, Wahyu, Fajar, Selvia, Sari
        </div>
      </div>

      <table class="w-full text-center border-collapse">
        <thead>
          <tr class="bg-gray-50">
            <th rowspan="2" class="w-16 p-1 font-normal border border-black">Process Step</th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Requirements</th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Potential Failure Mode</th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Potential Effect(s) of Failure</th>
            <th rowspan="2" class="w-10 p-1 font-normal border border-black">
              <div class="transform -rotate-90">Severity</div>
            </th>
            <th rowspan="2" class="w-10 p-1 font-normal border border-black">
              <div class="transform -rotate-90">Classification</div>
            </th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Potential Cause(s) of Failure</th>
            <th rowspan="2" class="w-10 p-1 font-normal border border-black">
              <div class="transform -rotate-90">Occurrence</div>
            </th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Controls Prevention</th>
            <th rowspan="2" class="p-1 font-normal border border-black w-44">Control Detection</th>
            <th rowspan="2" class="w-10 p-1 font-normal border border-black">
              <div class="transform -rotate-90">Detection</div>
            </th>
            <th rowspan="2" class="w-10 p-1 font-normal border border-black">
              <div class="transform -rotate-90">RPN</div>
            </th>
            <th rowspan="2" class="p-1 font-normal border border-black w-36">Recommended Action(s)</th>
            <th rowspan="2" class="w-24 p-1 font-normal border border-black">Responsibility</th>
            <th rowspan="2" class="w-24 p-1 font-normal border border-black">Target Completion Date</th>
            <th rowspan="2" class="w-24 p-1 font-normal border border-black">Action Taken Completion Date</th>
            <th colspan="4" class="p-1 font-normal border border-black">Result</th>
          </tr>
          <tr class="bg-gray-50">
            <th class="w-8 p-1 font-normal border border-black">
               <div class="transform -rotate-90">Severity</div>
            </th>
            <th class="w-8 p-1 font-normal border border-black">
               <div class="transform -rotate-90">Occurrence</div>
            </th>
            <th class="w-8 p-1 font-normal border border-black">
               <div class="transform -rotate-90">Detection</div>
            </th>
            <th class="w-8 p-1 font-normal border border-black">
               <div class="transform -rotate-90">RPN</div>
            </th>
          </tr>
        </thead>
        
        <tbody>
          <tr v-if="pfmeaData.length === 0">
            <td colspan="20" class="p-4 italic text-center text-gray-500 border border-black">
              Data dari database belum tersedia...
            </td>
          </tr>

          <tr v-for="(item, index) in pfmeaData" :key="index" class="hover:bg-gray-100">
            <td class="p-1 text-left border border-black">{{ item.processStep }}</td>
            <td class="p-1 text-left border border-black">{{ item.requirements }}</td>
            <td class="p-1 text-left border border-black">{{ item.failureMode }}</td>
            <td class="p-1 text-left border border-black">{{ item.effects }}</td>
            <td class="p-1 border border-black">{{ item.severity }}</td>
            <td class="p-1 border border-black">{{ item.classification }}</td>
            <td class="p-1 text-left border border-black">{{ item.causes }}</td>
            <td class="p-1 border border-black">{{ item.occurrence }}</td>
            <td class="p-1 text-left border border-black">{{ item.prevention }}</td>
            <td class="p-1 text-left border border-black">{{ item.detectionMethod }}</td>
            <td class="p-1 border border-black">{{ item.detectionScore }}</td>
            <td class="p-1 border border-black">{{ item.rpn }}</td>
            <td class="p-1 text-left border border-black">{{ item.recommendedAction }}</td>
            <td class="p-1 border border-black">{{ item.responsibility }}</td>
            <td class="p-1 border border-black">{{ item.targetDate }}</td>
            <td class="p-1 border border-black">{{ item.actionDate }}</td>
            <td class="p-1 border border-black">{{ item.resultSev }}</td>
            <td class="p-1 border border-black">{{ item.resultOcc }}</td>
            <td class="p-1 border border-black">{{ item.resultDet }}</td>
            <td class="p-1 border border-black">{{ item.resultRpn }}</td>
          </tr>
        </tbody>
      </table>
      
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Nanti tinggal di-replace aja array ini dari fetching API database (Laravel/CodeIgniter backend)
const pfmeaData = ref([
  // Contoh dummy structure dari gambar:
  /*
  {
    processStep: '10 Raw Material Receiving (P1)',
    requirements: 'Correct Raw Materials received',
    failureMode: 'Wrong Name/Code; Color; wrong spec, COA not attached',
    effects: 'Affects product functionality',
    severity: 5,
    classification: '',
    causes: 'Raw material supplier delivered wrong material',
    occurrence: 2,
    prevention: 'Carefully check actual received from order and compare with COA',
    detectionMethod: 'Check according to Raw Materials Receiving Confirmation Instruction; every receiving',
    detectionScore: 7,
    rpn: 70,
    recommendedAction: 'None',
    responsibility: '',
    targetDate: '',
    actionDate: '',
    resultSev: '',
    resultOcc: '',
    resultDet: '',
    resultRpn: ''
  }
  */
]);
</script>

<style scoped>
/* Sedikit styling tambahan untuk header vertikal agar rapi seperti di Excel */
th > div.transform {
  writing-mode: vertical-rl;
  text-orientation: mixed;
  transform: rotate(180deg);
  white-space: nowrap;
  display: inline-block;
  padding: 4px;
}
</style>