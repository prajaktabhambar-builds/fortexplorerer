<?php
/**
 * FORT EXPLORER - Sahyadri Trekking Safety & Heritage Expedition Guide
 * Comprehensive mountain protocols, seasonal advisories, equipment checklist, and emergency rescue directory.
 */

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<div class="py-5" style="background: linear-gradient(135deg, var(--fe-forest-dark) 0%, var(--fe-forest) 100%); color: #fff; border-bottom: 3px solid var(--fe-gold);">
    <div class="container py-3 text-center">
        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small mb-2">
            <i class="fa-solid fa-person-hiking me-1"></i>Official Trekker Guidelines
        </span>
        <h1 class="display-5 fw-bold font-heritage mb-2">Sahyadri Trekking Safety & Ethics</h1>
        <p class="lead text-white-50 max-w-700 mx-auto mb-0 fs-6">
            Preserve the pristine sanctity of Chhatrapati Shivaji Maharaj's historic fortresses while ensuring personal safety across rugged Western Ghats terrain.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Quick Emergency SOS Bar -->
    <div class="alert alert-danger rounded-4 p-4 shadow-sm mb-5 border-danger">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="display-6 text-danger"><i class="fa-solid fa-phone-volume"></i></div>
                <div>
                    <h5 class="fw-bold mb-1 text-danger">Sahyadri Mountain Emergency Helplines</h5>
                    <span class="small text-dark">Save these 24/7 rescue numbers in your phone before starting any trek.</span>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="tel:7620230230" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-mountain me-1"></i>MMRCC Rescue: 7620 230 230
                </a>
                <a href="tel:112" class="btn btn-dark btn-sm rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-shield me-1"></i>State Emergency: 112
                </a>
                <a href="tel:108" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold bg-white">
                    <i class="fa-solid fa-truck-medical me-1"></i>Ambulance: 108
                </a>
                <a href="tel:1926" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold bg-white">
                    <i class="fa-solid fa-tree me-1"></i>Forest Dept: 1926
                </a>
            </div>
        </div>
    </div>

    <!-- Seasonal Protocols Grid -->
    <h3 class="font-heritage text-success mb-4 text-center">
        <i class="fa-solid fa-cloud-sun-rain me-2 text-warning"></i>Seasonal Sahyadri Guidelines
    </h3>

    <div class="row g-4 mb-5">
        <!-- Monsoon -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded-4 shadow-sm border border-top border-4 border-primary h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-cloud-showers-heavy text-primary fs-3"></i>
                    <h5 class="font-heritage mb-0 text-dark">Monsoon Season (June – Sept)</h5>
                </div>
                <p class="small text-muted mb-3">While the Sahyadris are lush green and waterfalls peak, heavy downpours pose significant hazards.</p>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i><strong>Slippery Basalt:</strong> Wet rock steps and mossy trails have near-zero friction. Wear boots with deep rubber lugs.</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i><strong>Flash Waterfalls:</strong> Never linger under steep gushing gorges; water volume can multiply within minutes.</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i><strong>Dense Cloud Mist:</strong> High fort plateaus frequently get enveloped in thick fog. Stay on main marked trails; cliffs are vertical drops.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Carry dry bags / waterproof pouches for all electronics and food.</li>
                </ul>
            </div>
        </div>

        <!-- Winter -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded-4 shadow-sm border border-top border-4 border-info h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-snowflake text-info fs-3"></i>
                    <h5 class="font-heritage mb-0 text-dark">Winter Season (Oct – Feb)</h5>
                </div>
                <p class="small text-muted mb-3">The prime season for overnight camping, multi-day circuits, and crystal-clear summit sunrises.</p>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i><strong>Ideal Temperatures:</strong> Pleasant daytime hiking (20-25°C) with brisk mountain breezes.</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i><strong>Night Chill:</strong> High peaks like Rajgad, Torna, and Harishchandragad drop to 8-12°C. Carry warm fleece and thermal sleeping bags.</li>
                    <li><i class="fa-solid fa-fire text-danger me-2"></i><strong>Campfire Rules:</strong> Never kindle open campfires near dry grass or ancient wooden pillars. Extinguish all embers thoroughly with water.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Carry headlamps with spare batteries; winter twilights fade quickly after 6:00 PM.</li>
                </ul>
            </div>
        </div>

        <!-- Summer -->
        <div class="col-lg-4">
            <div class="bg-white p-4 rounded-4 shadow-sm border border-top border-4 border-warning h-100">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-sun text-warning fs-3"></i>
                    <h5 class="font-heritage mb-0 text-dark">Summer Season (March – May)</h5>
                </div>
                <p class="small text-muted mb-3">Extreme heat and dry rock require strict discipline and early dawn starts.</p>
                <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                    <li><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i><strong>Hydration Crucial:</strong> Minimum 3.5 liters of water per person. Add electrolyte packets (ORS / Glucon-D).</li>
                    <li><i class="fa-solid fa-clock text-primary me-2"></i><strong>Alpine Dawn Ascent:</strong> Start climbing by 5:30 AM to summit before the scorching 11:00 AM sun. Complete descent before noon.</li>
                    <li><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i><strong>Dried Water Tanks:</strong> Many fort rock cisterns dry out or become stagnant by April. Verify water availability at the base village before ascending.</li>
                    <li><i class="fa-solid fa-circle-check text-success me-2"></i>Wear broad-brimmed hats, polarized sunglasses, and full-sleeve UV-protection shirts.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Interactive Equipment Checklist -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border h-100">
                <h5 class="font-heritage text-success mb-3">
                    <i class="fa-solid fa-clipboard-check me-2 text-warning"></i>Essential Sahyadri Trek Gear Checklist
                </h5>
                <p class="small text-muted mb-4">Tap items to track your packing status before heading into the mountains:</p>
                
                <div class="d-flex flex-column gap-2" id="packingChecklist">
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0" checked>
                        <span class="small fw-bold">Trekking Shoes with deep rubber lugs (Avoid flat sneakers)</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0" checked>
                        <span class="small fw-bold">Minimum 2.5 to 3 Liters Drinking Water per person</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">Electrolyte / ORS powders and glucose tablets</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">Compact First Aid Kit (Crepe bandage, antiseptic, pain spray)</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">LED Headlamp / Flashlight with spare batteries</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">Emergency Whistle (audible across misty valleys)</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">High-energy snacks (dry fruits, chikki, energy bars)</span>
                    </label>
                    <label class="p-2.5 rounded-3 border bg-light d-flex align-items-center gap-3 cursor-pointer">
                        <input type="checkbox" class="form-check-input mt-0">
                        <span class="small fw-bold">Garbage / Trash Bag (Zero littering rule)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Fort Heritage Code of Ethics (Do's & Don'ts) -->
        <div class="col-lg-6">
            <div class="bg-white p-4 p-md-5 rounded-4 shadow-sm border h-100">
                <h5 class="font-heritage text-danger mb-3">
                    <i class="fa-solid fa-shield-halved me-2 text-warning"></i>Heritage Ethics & Fort Code of Conduct
                </h5>
                <p class="small text-muted mb-3">These forts were consecrated by the sacrifices of Maratha warriors. Every visitor is a custodian.</p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-success-subtle rounded-3 border border-success h-100">
                            <h6 class="fw-bold text-success mb-2"><i class="fa-solid fa-circle-check me-1"></i>DO's</h6>
                            <ul class="list-unstyled small text-dark d-flex flex-column gap-1 mb-0">
                                <li>&bull; Treat memorials and temples with deep reverence.</li>
                                <li>&bull; Bring back every piece of your plastic and waste.</li>
                                <li>&bull; Support local village homestays and guides.</li>
                                <li>&bull; Greet fellow trekkers with <em>"Jai Shivrai"</em>.</li>
                                <li>&bull; Inform base villagers of your trek group size and estimated return time.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-danger-subtle rounded-3 border border-danger h-100">
                            <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-ban me-1"></i>DON'Ts</h6>
                            <ul class="list-unstyled small text-dark d-flex flex-column gap-1 mb-0">
                                <li>&bull; <strong>Strictly NO Alcohol or Drugs:</strong> Forts are sanctified historical memorials.</li>
                                <li>&bull; <strong>NO Graffiti:</strong> Carving names on ramparts is a punishable offense under Heritage Acts.</li>
                                <li>&bull; Never climb on crumbling bastions or loose parapets.</li>
                                <li>&bull; Avoid loud Bluetooth speakers that disturb wildlife.</li>
                                <li>&bull; Do not contaminate sacred water cisterns (Devtake).</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border mt-3 small">
                    <strong class="text-dark d-block mb-1"><i class="fa-solid fa-staff-snake text-danger me-1"></i>Snake Safety Note:</strong>
                    <span>Sahyadri trails are home to the Bamboo Pit Viper and Russell's Viper. Always watch where you place your feet and hands; use a trekking pole to check heavy leaf litter. In the rare event of a bite, keep the patient calm, immobilize the limb, and rush immediately to the nearest Primary Health Centre (PHC).</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
