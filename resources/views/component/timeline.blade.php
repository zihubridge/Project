{{-- <template>
  <div class="container roadmap my-5 position-relative text-white mb-5">
    <!-- Background Images -->
    <img :src="bgImage" alt="bg left" class="roadmap-bg bg-left d-none-responsive" />
    <img :src="bgImage" alt="bg right" class="roadmap-bg bg-right d-none-responsive" />

    <div class="text-center mb-5">
      <small class="text-gradient">The Journey Ahead</small>
      <h2 class="fw-bold">Our Roadmap</h2>
      <div class="d-flex gap-3 justify-content-center align-items-center mb-2 hero-section-top">
        <img :src="iconImage" alt="icon" class="img-fluid" />
        <p class="">Simple, efficient, and direct.</p>
      </div>
    </div>

    <!-- Timeline wrapper -->
    <div class="timeline position-relative" id="timeline" :style="{ '--line-progress': lineProgress + '%' }"
      ref="timelineRef">
      <!-- Q1 -->
      <div class="timeline-item right">
        <div class="timeline-content">
          <h6>Q1 2025</h6>
          <p>Stellar ↔ Ripple live.</p>
        </div>
      </div>

      <!-- Q2 -->
      <div class="timeline-item left">
        <div class="timeline-content">
          <h6>Q2 2025</h6>
          <p>Add Solana + HBAR support.</p>
        </div>
      </div>

      <!-- Q3 -->
      <div class="timeline-item right">
        <div class="timeline-content">
          <h6>Q3 2025</h6>
          <p>Governance design, scaling up chains.</p>
        </div>
      </div>

      <!-- Q4 -->
      <div class="timeline-item left">
        <div class="timeline-content">
          <h6>Q4 2025</h6>
          <p>Fiat on-ramp integration.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref, onMounted, onBeforeUnmount } from "vue";
  import iconImage from "../assets/images/icon.png";
  import bgImage from "../assets/images/Rectangle.png";

  const maxLinePercent = 80;
  const lineProgress = ref(0);
  const timelineRef = ref(null);

  const handleScroll = () => {
    const timeline = timelineRef.value;
    if (!timeline) return;

    const timelineRect = timeline.getBoundingClientRect();
    const timelineTop = timelineRect.top + window.scrollY;
    const timelineHeight = timeline.offsetHeight;
    const windowHeight = window.innerHeight;
    const scrollTop = window.scrollY;

    let progress = ((scrollTop + windowHeight - timelineTop) / timelineHeight) * 100;
    progress = Math.max(0, Math.min(progress, 100));

    const viewportBottom = scrollTop + windowHeight;
    const timelineBottom = timelineTop + timelineHeight;
    const isFullyReached = viewportBottom >= timelineBottom - 1;

    requestAnimationFrame(() => {
      const scaled = (progress * maxLinePercent) / 100;
      lineProgress.value = isFullyReached ? 100 : scaled;

      if (isFullyReached) {
        timeline.classList.add("active");
      } else {
        timeline.classList.remove("active");
      }
    });

    const activationViewportRatio = 0.9;
    const activationLine = windowHeight * activationViewportRatio;

    const items = timeline.querySelectorAll(".timeline-item");
    items.forEach((item) => {
      const content = item.querySelector(".timeline-content");
      const itemRect = item.getBoundingClientRect();
      const itemMidpoint = itemRect.top + itemRect.height / 2;

      if (itemMidpoint <= activationLine || isFullyReached) {
        item.classList.add("active");
        content?.classList.add("active");
      } else {
        item.classList.remove("active");
        content?.classList.remove("active");
      }
    });
  };

  onMounted(() => {
    window.addEventListener("scroll", handleScroll);
    handleScroll();
  });

  onBeforeUnmount(() => {
    window.removeEventListener("scroll", handleScroll);
  });
</script> --}}

<div class="container roadmap my-5 position-relative text-white mb-5">
  <!-- Background Images -->
  <img src="{{ asset('assets/images/Rectangle.png') }}" alt="bg left" class="roadmap-bg bg-left d-none-responsive" />
  <img src="{{ asset('assets/images/Rectangle.png') }}" alt="bg right" class="roadmap-bg bg-right d-none-responsive" />

  <div class="text-center mb-5">
    <small class="text-gradient">The Journey Ahead</small>
    <h2 class="fw-bold">Our Roadmap</h2>
    <div class="d-flex gap-3 justify-content-center align-items-center mb-2 hero-section-top">
      <img src="{{ asset('assets/images/icon.png') }}" alt="icon" class="img-fluid" />
      <p class="">Simple, efficient, and direct.</p>
    </div>
  </div>

  <!-- Timeline wrapper -->
  <div class="timeline position-relative" id="timeline">
    <!-- Q1 -->
    <div class="timeline-item right">
      <div class="timeline-content">
        <h6>Q1 2025</h6>
        <p>Stellar ↔ Ripple live.</p>
      </div>
    </div>

    <!-- Q2 -->
    <div class="timeline-item left">
      <div class="timeline-content">
        <h6>Q2 2025</h6>
        <p>Add Solana + HBAR support.</p>
      </div>
    </div>

    <!-- Q3 -->
    <div class="timeline-item right">
      <div class="timeline-content">
        <h6>Q3 2025</h6>
        <p>Governance design, scaling up chains.</p>
      </div>
    </div>

    <!-- Q4 -->
    <div class="timeline-item left">
      <div class="timeline-content">
        <h6>Q4 2025</h6>
        <p>Fiat on-ramp integration.</p>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const maxLinePercent = 80;
    let lineProgress = 0;
    const timeline = document.getElementById('timeline');

    const handleScroll = () => {
      if (!timeline) return;

      const timelineRect = timeline.getBoundingClientRect();
      const timelineTop = timelineRect.top + window.scrollY;
      const timelineHeight = timeline.offsetHeight;
      const windowHeight = window.innerHeight;
      const scrollTop = window.scrollY;

      let progress = ((scrollTop + windowHeight - timelineTop) / timelineHeight) * 100;
      progress = Math.max(0, Math.min(progress, 100));

      const viewportBottom = scrollTop + windowHeight;
      const timelineBottom = timelineTop + timelineHeight;
      const isFullyReached = viewportBottom >= timelineBottom - 1;

      requestAnimationFrame(() => {
        const scaled = (progress * maxLinePercent) / 100;
        lineProgress = isFullyReached ? 100 : scaled;
        timeline.style.setProperty('--line-progress', lineProgress + '%');

        if (isFullyReached) {
          timeline.classList.add('active');
        } else {
          timeline.classList.remove('active');
        }
      });

      const activationViewportRatio = 0.9;
      const activationLine = windowHeight * activationViewportRatio;

      const items = timeline.querySelectorAll('.timeline-item');
      items.forEach((item) => {
        const content = item.querySelector('.timeline-content');
        const itemRect = item.getBoundingClientRect();
        const itemMidpoint = itemRect.top + itemRect.height / 2;

        if (itemMidpoint <= activationLine || isFullyReached) {
          item.classList.add('active');
          content?.classList.add('active');
        } else {
          item.classList.remove('active');
          content?.classList.remove('active');
        }
      });
    };

    window.addEventListener('scroll', handleScroll);
    handleScroll();

    window.addEventListener('beforeunload', () => {
      window.removeEventListener('scroll', handleScroll);
    });
  });
</script>