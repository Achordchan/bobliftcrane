import os, sys
import matplotlib; matplotlib.use('Agg')
# usage: python3 charts.py [source-dir]  ->  <source-dir>/forklift-charts/*.png (default: $CONTENT_GAP_SOURCE, else tools/content-gap/source)
SOURCE = sys.argv[1] if len(sys.argv) > 1 else os.environ.get('CONTENT_GAP_SOURCE') or os.path.join(os.path.dirname(os.path.abspath(__file__)), 'source')
OUT = os.path.join(SOURCE, 'forklift-charts')
os.makedirs(OUT, exist_ok=True)
import matplotlib.pyplot as plt, numpy as np
models=[('2.3 t (CC85)',2.3,50,5.4,560,'#c8102e'),('2.5 t (CC105)',2.5,50,6.6,650,'#e8792b'),('3.5 t (CC125)',3.5,60,8.0,850,'#2b6cb0'),('7 t (CC165)',7.0,180,15.2,2000,'#3a8a5a')]
ink='#1f2430';grey='#5b6270'
fig,ax=plt.subplots(figsize=(14.4,8.8),dpi=100)
for name,cap,mom,dmax,w,c in sorted(models,key=lambda m:m[0].startswith('2.3')):
    d0=mom/(cap*9.81); d=np.linspace(d0,dmax,200); y=np.minimum(cap,mom/d/9.81)
    ax.plot(d,y,color=c,lw=2.4 if name.startswith('2.3') else 3.8 if name.startswith('2.5') else 3.2,label=name,zorder=4 if name.startswith('2.3') else 3); ax.scatter([dmax],[mom/dmax/9.81],color=c,s=90,zorder=5)
    off={'2.3':(-6,-34),'2.5':(4,-42),'3.5':(0,16),'7 t':(-4,16)}[name[:3]]
    ha='left' if name.startswith('2.5') else 'right'
    ax.annotate(f'{mom/dmax/9.81:.1f} t @ {dmax:g} m',(dmax,mom/dmax/9.81),xytext=off,textcoords='offset points',ha=ha,color=c,fontsize=15,fontweight='bold')
ax.set_xlim(0,16);ax.set_ylim(0,7.4);ax.set_xticks(range(0,17,2))
ax.set_xlabel('Working distance from mast (m)',fontsize=16,color=grey);ax.set_ylabel('Load (t)',fontsize=16,color=grey)
ax.tick_params(colors=grey,labelsize=15);ax.grid(axis='y',color='#e6e6e6');ax.set_axisbelow(True)
for s in ['top','right','left']:ax.spines[s].set_visible(False)
ax.spines['bottom'].set_color('#555')
ax.legend(frameon=False,fontsize=16,loc='upper right',labelcolor=ink)
fig.text(0.07,0.955,'Capacity falls as the hook moves out',fontsize=25,fontweight='bold',color=ink)
fig.text(0.07,0.915,'Estimated upper-bound load (t) vs. working distance (m), from max lifting moment',fontsize=16,color=grey)
fig.text(0.07,0.015,'Estimate = max lifting moment ÷ distance ÷ 9.81, capped at rated capacity. Planning only; the load chart governs.',fontsize=13.5,color=grey)
fig.subplots_adjust(left=0.07,right=0.97,top=0.87,bottom=0.15)
fig.savefig(os.path.join(OUT, 'forklift-capacity-vs-reach.png'));plt.close()
fig,ax=plt.subplots(figsize=(16,6),dpi=100)
ys=list(range(len(models)))[::-1]
for y,(name,cap,mom,dmax,w,c) in zip(ys,models):
    ax.hlines(y,0,dmax,color='#c8102e',lw=4);ax.scatter([dmax],[y],color='#c8102e',s=260,zorder=5)
    ax.text(dmax+0.35,y,f'{dmax:g} m',fontsize=19,fontweight='bold',color=ink,va='center')
    ax.text(19.3,y,f'{w:,} kg',fontsize=19,color=grey,va='center',ha='right')
ax.set_yticks(ys);ax.set_yticklabels([m[0] for m in models],fontsize=19,fontweight='bold',color=ink)
ax.set_xlim(0,19.4);ax.set_xticks([0,4,8,12,16]);ax.set_xticklabels([f'{t} m' for t in [0,4,8,12,16]],fontsize=17,color=grey)
ax.grid(axis='x',color='#e0e0e0');ax.set_axisbelow(True);ax.tick_params(length=0)
for s in ['top','right','left','bottom']:ax.spines[s].set_visible(False)
fig.text(0.03,0.93,'How far each model reaches',fontsize=25,fontweight='bold',color=ink)
fig.text(0.03,0.855,'Maximum working distance (m) and attachment self-weight',fontsize=17,color=grey)
fig.subplots_adjust(left=0.17,right=0.97,top=0.80,bottom=0.12)
fig.savefig(os.path.join(OUT, 'forklift-max-working-distance.png'));plt.close()
